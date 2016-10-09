<?php
namespace keeko\application\admin;

use keeko\account\AccountModule;
use keeko\framework\foundation\AbstractApplication;
use keeko\framework\security\AuthManager;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Matcher\UrlMatcher;
use Symfony\Component\Routing\RequestContext;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;

/**
 * Keeko Admin Application
 *
 * @license MIT
 * @author gossi
 */
class AdminApplication extends AbstractApplication {

	const EXT_MENU = 'keeko.admin.menu';

	/**
	 * @param Request $request
	 */
	public function run(Request $request) {
		$menu = $this->generateMenu();

		try {
			$routes = $this->generateRoutes();
			$context = new RequestContext($this->getBaseUrl());
			$matcher = new UrlMatcher($routes, $context);

			$match = $matcher->match($this->getDestination());
			$route = $match['_route'];

			switch ($route) {
				case 'login':
					$response = $this->login($request);
					break;

				case 'logout':
					$response = $this->logout($request);
					break;


				case 'index':
					$response = $this->index($request);
					break;

				default:
					// do find action here
// 					$kernel = $this->getServiceContainer()->getKernel();
// 					$response = $kernel->handle($action, $request);
			}

			if ($response instanceof RedirectResponse) {
				return $response;
			}

			$main = $response->getContent();
		} catch (PermissionDeniedException $e) {
			$main = 'Permission Denied';
		} catch (\Exception $e) {
			$main = 'Error: ' . $e->getMessage();
		}

		$response = new Response($this->render('/keeko/admin-app/templates/main.twig', [
			'main' => $main,
			'menu' => $menu
		]));

		return $response;
	}

	private function generateMenu() {
		$manager = $this->getServiceContainer()->getModuleManager();
		$reg = $this->getServiceContainer()->getExtensionRegistry();
		$items = $reg->getExtensions(AdminApplication::EXT_MENU);
		$menu = ['' => []];

		// build routes
		foreach ($items as $item) {
			$parent = isset($item['parent']) ? $item['parent'] : '';
			if (!isset($menu[$parent])) {
				$menu[$parent] = [];
			}

			$manager->load(str_replace('.', '/', $item['domain']));
			$slug = isset($item['module']) ? '/' . $item['module'] . '/' . $item['action'] : '#';

			$menu[$parent][$item['id']] = array_merge([
				'slug' => $slug
			], $item);
		}

// 		$ext = $routes[$section];
// 		$kernel = $this->getServiceContainer()->getKernel();
// 		$module = $this->getServiceContainer()->getModuleManager()->load($ext['module']);
// 		$action = $module->loadAction($ext['action'], 'html');
// 		$response = $kernel->handle($action, $request);

		return $menu;
	}

	private function generateRoutes() {
		$routes = new RouteCollection();

		$auth = $this->getServiceContainer()->getAuthManager();
		if ($auth->isAuthenticated()) {
			$reg = $this->getServiceContainer()->getExtensionRegistry();
			$items = $reg->getExtensions(AdminApplication::EXT_MENU);

			foreach ($items as $item) {
				if (isset($item['module']) && isset($item['action'])) {
					$path = $item['module'] . '/' . $item['action'];
					$routes->add($item['id'],  new Route($path));
				}
			}
		}

		$routes->add('login', new Route('/login'));
		$routes->add('logout', new Route('/logout'));
		$routes->add('index', new Route('/'));

		return $routes;
	}

	private function login(Request $request) {
		$error = null;
		if ($request->isMethod('POST')) {
			$auth = $this->getServiceContainer()->getAuthManager();
			if ($auth->login($request->request->get('login'), $request->request->get('password'))) {
				$token = $auth->getSession()->getToken();
				$response = new RedirectResponse($this->getBaseUrl());
				$this->getServiceContainer()->getKernel()->setCookie($response, AuthManager::COOKIE_TOKEN_NAME, $token);
				return $response;
			} else {
				$error = 'Invalid credentials';
			}
		}

		/** @var $account AccountModule */
		$account = $this->getServiceContainer()->getModuleManager()->load('keeko/account');
		$factory = $account->getWidgetFactory();
		$widget = $factory->createLoginWidget();

		return new Response($widget->build([
			'destination' => $this->getBaseUrl() . '/login',
			'error' => $error,
			'login' => $request->request->get('login')
		]));
	}

	private function logout(Request $request) {
		$auth = $this->getServiceContainer()->getAuthManager();
		$auth->logout();

		return new RedirectResponse($this->getBaseUrl());
	}

	private function index(Request $request) {
		$auth = $this->getServiceContainer()->getAuthManager();
		return $auth->isAuthenticated() ? $this->dashboard($request) : $this->login($request);
	}

	private function dashboard(Request $request) {
		return new Response('dashboard');
	}
}

