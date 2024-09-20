# Readme: Routing

> $Id$ ($Date$)

HTTP Routing using php **Attributes**.

## Intro: Attributes

What is an **Attribute**? It's a class just like any other class only with the `Attribute` **Attribute**. So why are you treating it more like an `enum` or `Map` that can only hold a few values describing something? You don't do it with the classes you uses your custom attributes on! But I don't blame you, it all comes down to some pour choices in wording used by the documentation.

So how should I be think of **Attributes**? As classes naturally. Classes to object that get things done to be more exact. That `#[Route(name: 'home', path: '/')]` like might make more sense when you start looking at it like this: `$route = new Route('/', 'home');`. Here a fun experiment to try; remove the `Attribute` from `Route` then have the `Router` take an array of `Route` parameters as argument. Easy, wasn't it and you understand Attributes and with practice you spot many more classes you can use as such.

Hope that gets you thinking about **Attributes** in a new, more realistic manor that leads to you adding that `#[Attribute]` line to a good many more classes.

## Install

`composer require inanepain/routing`

## Usage

Quick overview showing the bits relating to the `Route` `Attribute` in two examples. Neither are complete, though the simple example would run with minimum fuss. Check the Appendix for the `.htaccess` file you will need to use with the `index.php` file.

## Example: Simple

Super simple example using **php** built in web server, `php -S localhost:8080 -t public index.php`.

**MainController.php:**

```php
class MainController {
    ...

    #[Route(path: '/', name: 'home')]
    public function home(): void {
        ...
        echo <<<HTML
...
HTML;
    }

    ...

    #[Route(path: '/product/{product}', name: 'product', )]
    public function productTask(array $params): void {
        $sql = "...where product_id = '{$params["product"]}'";
        ...
        echo <<<HTML
...
HTML;
    }

    ...
}
```

**index.php:**

```php
use App\Controller\MainController;
use Inane\Routing\Router;

require_once 'vendor/autoload.php';

$file = 'public' . $_SERVER['REQUEST_URI'];

// Server existing files in web dir
if (file_exists($file) && !is_dir($file)) return false;

$router = new Router();
$router->addRoutes([MainController::class]);

if ($match = $router->match()) {
    $controller = new $match['class']();
    $controller->{$match['method']}($match['params']);
} else {
    throw new Exception('Request Error: Unmatched `file` or `route`!');
}

```

## Example: Application

Slightly more complex example.

#### The various pieces

**IndexController.php:**

```php
class IndexController extends AbstractController {
    ...

    #[Route(path: '/', name: 'home')]
    public function indexTask(): array {
        ...
    }

    ...

    #[Route(path: '/product/{product}', name: 'product', )]
    public function productTask(): array {
        ...
    }

    ...

    #[Route(path: '/product/{product}/review/{id<\d+>}', name: 'product-review')]
    public function reviewTask(): array {
        ...
    }

    ...
}
```

**index.phtml (view template):**

```phtml
...
<a class="menu-item" href="<?=$route->url('product', ['product' => $item['id']])?>"><?=$item['name_long']?></a>
...
```

**website (rendered view):**

```html
<a class="menu-item" href="/product/mega-maid">Mega Maid (Household Robot Helper)</a>
```

#### Putting it all together

**Application.php:**

```php
class Application {
    ...

    protected function initialise(): void {
        ...
        $this->router = new Router([
            IndexController::class,
            ...
            WhoopsController::class,
            ...
        ]);
        ...
    }

    ...

    public function run(): void {
        ...
        if ($match = $this->router->match()) {
            $controller = new $match['class']($match['params']);
            $data = $controller->{$match['method']}();
            ...
            $body = $this->renderer->render($template, $data);
            ...
        }
        ...
    }

    ...
}
```

## Appendix: .htaccess

You will also need to do some magic in your `.htaccess` file so that `index.php` handles all requests.

```appache
RewriteEngine On
# The following rule tells Apache that if the requested filename exists, simply serve it.
RewriteCond %{REQUEST_FILENAME} -s [OR]
RewriteCond %{REQUEST_FILENAME} -l [OR]
RewriteCond %{REQUEST_FILENAME} -d
RewriteRule ^.*$ - [L]

# The following rewrites all other queries to index.php. The 
# condition ensures that if you are using Apache aliases to do
# mass virtual hosting or installed the project in a subdirectory,
# the base path will be prepended to allow proper resolution of
# the index.php file; it will work in non-aliased environments
# as well, providing a safe, one-size fits all solution.
RewriteCond %{REQUEST_URI}::$1 ^(/.+)/(.*)::\2$
RewriteRule ^(.*) - [E=BASE:%1]
RewriteRule ^(.*)$ %{ENV:BASE}/index.php [L]

<Limit GET HEAD POST PUT DELETE OPTIONS>
# Deprecated apache 2.2 syntax:
# Order Allow,Deny
# Allow from all
# Apache > 2.4 requires:
Require all granted
</Limit>
```
