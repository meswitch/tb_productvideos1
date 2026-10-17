# Hello ThirtyBeeS

Example module for the [ThirtyBeeS](https://thirtybees.com) CMS/e-commerce platform.

It displays a configurable greeting message on the storefront homepage via the
`displayHome` hook, and includes a back-office settings page built with `HelperForm`.

## Requirements

- ThirtyBeeS 1.x (or PrestaShop 1.6+ compatible — the API is nearly identical)
- PHP 5.6+

## Installation

1. Copy the `hello_thirtybees` folder into `<shop-root>/modules/`.
2. In the back office, go to **Modules → Modules & Services**.
3. Search for **Hello ThirtyBeeS** and click **Install**.
4. Click **Configure** to change the greeting message.

Alternatively, zip this folder and upload it via **Modules → Add new module**.

## Structure

```
hello_thirtybees/
├── hello_thirtybees.php              # Module main class (install, hooks, config form)
├── README.md
└── views/
    ├── css/
    │   └── hello_thirtybees.css      # Front-office styles (added via header hook)
    └── templates/
        └── hook/
            └── hello_thirtybees.tpl  # Smarty template rendered on displayHome
```

## Hooks used

| Hook         | Purpose                                  |
|--------------|------------------------------------------|
| `displayHome`| Renders the greeting block on homepage   |
| `header`     | Adds the module CSS file to the page     |

## Extending this module

- Register more hooks by adding `$this->registerHook('hookName')` in `install()`
  and implementing `public function hookHookName($params)` in the class.
- Store data in database tables: create them in `install()` with `Db::getInstance()->execute()`,
  drop them in `uninstall()`.
- Translations: strings passed to `$this->l()` and `{l s='...' mod='hello_thirtybees'}`
  are automatically extracted when you save translations in the back office.

## License

MIT
