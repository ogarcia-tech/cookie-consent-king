# Welcome to your Lovable project

## Project info

**URL**: https://lovable.dev/projects/1da458a0-5313-4e8e-8699-a605a08fdfea

## How can I edit this code?

There are several ways of editing your application.

**Use Lovable**

Simply visit the [Lovable Project](https://lovable.dev/projects/1da458a0-5313-4e8e-8699-a605a08fdfea) and start prompting.

Changes made via Lovable will be committed automatically to this repo.

**Use your preferred IDE**

If you want to work locally using your own IDE, you can clone this repo and push changes. Pushed changes will also be reflected in Lovable.

The only requirement is having Node.js & npm installed - [install with nvm](https://github.com/nvm-sh/nvm#installing-and-updating)

Follow these steps:

```sh
# Step 1: Clone the repository using the project's Git URL.
git clone <YOUR_GIT_URL>

# Step 2: Navigate to the project directory.
cd <YOUR_PROJECT_NAME>

# Step 3: Install the necessary dependencies.
npm i

# Step 4: Start the development server with auto-reloading and an instant preview.
npm run dev
```

**Edit a file directly in GitHub**

- Navigate to the desired file(s).
- Click the "Edit" button (pencil icon) at the top right of the file view.
- Make your changes and commit the changes.

**Use GitHub Codespaces**

- Navigate to the main page of your repository.
- Click on the "Code" button (green button) near the top right.
- Select the "Codespaces" tab.
- Click on "New codespace" to launch a new Codespace environment.
- Edit files directly within the Codespace and commit and push your changes once you're done.

## What technologies are used for this project?

This project is built with:

- Vite
- TypeScript
- React
- shadcn-ui
- Tailwind CSS

## Running tests

Install dependencies and run the Vitest suite:

```sh
npm install
npm test
```

## How can I deploy this project?

Simply open [Lovable](https://lovable.dev/projects/1da458a0-5313-4e8e-8699-a605a08fdfea) and click on Share -> Publish.

## Can I connect a custom domain to my Lovable project?

Yes, you can!

To connect a domain, navigate to Project > Settings > Domains and click Connect Domain.

Read more here: [Setting up a custom domain](https://docs.lovable.dev/tips-tricks/custom-domain#step-by-step-guide)

## Quick start

```sh
# Install dependencies and start developing
npm i
npm run dev

# Build optimized assets
npm run build
```

The build command generates production files in the `dist/` directory. CSS and JavaScript are placed inside `dist/assets/`.

## Enqueuing in WordPress

Use the plugin wrapper (`cookie-banner-plugin.php`) to load the build output:

```php
function cck_enqueue_assets() {
    $plugin_url = plugin_dir_url(__FILE__);
    wp_enqueue_style('cck-style', $plugin_url . 'dist/assets/index.css', [], COOKIE_BANNER_VERSION);
    wp_enqueue_script('cck-script', $plugin_url . 'dist/assets/index.js', [], COOKIE_BANNER_VERSION, true);
}
add_action('wp_enqueue_scripts', 'cck_enqueue_assets');
```

The wrapper also loads translations via `load_plugin_textdomain()` so WordPress can read the files under `languages/`.

## Translations

Available `.po` files are located in `languages/`:

- `cookie-banner-en_US.po`
- `cookie-banner-de_DE.po`
- `cookie-banner-es_ES.po`
- `cookie-banner-fr_FR.po`
- `cookie-banner-it_IT.po`

Load them in the plugin wrapper with:

```php
load_plugin_textdomain('cookie-banner', false, dirname(plugin_basename(__FILE__)) . '/languages');
```

