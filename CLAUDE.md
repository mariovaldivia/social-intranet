# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Layout

Social intranet (timeline of posts, comments, likes, events, employee profiles) built with PHP 8.4 and Symfony 7.4 LTS, run in Docker.

- Repo root: Docker setup (`docker-compose.yml`, `docker/Dockerfile`, `Makefile`) and `.env` with MySQL credentials used by compose.
- `intranet/`: the Symfony app. All `bin/console`, `composer` and `phpunit` commands run from here. `intranet/compose.yaml` is the unused Symfony Flex default (Postgres); the real stack is the root `docker-compose.yml`.

## Environment and commands

Containers: `application` (PHP-FPM + Symfony CLI, repo mounted at `/appdata/www`, working dir `/appdata/www/intranet`) and `db` (MySQL 8.0, host port 33061). The app is served on http://localhost:1000.

```sh
make build            # build images
make start            # docker compose up -d
make run              # symfony serve -d --allow-all-ip (needed so port 1000 reaches the container)
make ssh-be           # shell in the app container, as the host UID
make composer-install
make code-style       # php-cs-fixer with @Symfony rules on src/
```

Run commands in the container as the host UID so files stay owned by you: `docker exec --user $(id -u) application <cmd>`. The image creates `appuser` with the host UID (build arg `U_ID`), so that user has a home dir.

Database setup:

```sh
php bin/console doctrine:migrations:migrate
php bin/console doctrine:fixtures:load   # purges DB; users mvaldivia@gmail.com / jperez@gmail.com, password intranet123
```

Migrations are committed in `intranet/migrations/`. Only run `make:migration` after changing an entity, and commit the result. Check entities and migrations are in sync with `doctrine:migrations:diff` (should report "No changes"); `doctrine:schema:validate` always flags `doctrine_migration_versions` and that is expected. Do not add a DBAL `schema_filter` for that table: it hides the table from Doctrine Migrations, which then reports 0 executed migrations.

Useful checks: `php bin/console lint:container`, `php bin/console lint:twig templates`, `composer audit`.

Tests (PHPUnit 9 via `bin/phpunit`, functional tests in `tests/Controller/`):

```sh
make test                                          # migrates the test DB, then runs the suite
php bin/phpunit --filter testIndex tests/Controller/EventControllerTest.php
```

Tests use the `intranet_test` database (Doctrine appends `_test` in the test env). `docker/mysql/init/01-test-database.sh` creates it and grants the app user access, but MySQL only runs it on a fresh `db` volume; on an older volume run `make test-db` once. Run `php bin/console --env=test doctrine:migrations:migrate` after adding migrations (`make test` does this).

Every route requires login, so functional tests must authenticate: use `App\Tests\Traits\LogsInUserTrait::logIn($client, $manager, $roles)`, which creates a test user with a `Profile` (`base.html.twig` dereferences `app.user.profile`). Most CRUD tests in `EventControllerTest`/`ProfileControllerTest` are still `make:crud` scaffolding marked incomplete.

## Architecture

**Auth.** `form_login` against `User` by email; everything except `/login` requires `ROLE_USER`. `/admin` (EasyAdmin, `src/Controller/Admin/`) requires `ROLE_ADMIN`; the admin UI has no roles field, so grant it in the `user.roles` JSON column (fixtures make `mvaldivia@gmail.com` admin). CSRF is stateless (`config/packages/csrf.yaml`): forms render a fixed `csrf-token` value and validation relies on the request's `Origin`/`Referer` header, so scripted POSTs must send a same-origin `Origin` header.

**User vs Profile.** `Profile` is the employee record (name, identification, department, photo); `User` is the login account, one-to-one with `Profile` (owning side on `User`). Creating a profile in `ProfileController::new` also creates its `User`, with username from `Profile::generateUsername()` (`name.lastname`) and the identification number as initial password. Org structure: `Management` 1-n `Department` 1-n `Profile`.

**Timeline.** `TimelineController` serves the timeline, user walls (`/wall/{username}`), comments and likes. A `Post` either has a user message or is linked to an `Event`: `EventController::new` calls `TimelineService::addEvent()`, which creates a message-less post for the event. Because of that, `Post::$message` is nullable and the "not blank" rule lives on `PostType`, not the entity. `Like` targets either a post or a comment. Timestamps (`date`, `createdAt`) are set in `#[ORM\PrePersist]` callbacks on the entities.

**Frontend.** AssetMapper + importmap (no Node build). Styling is Tailwind CSS 4 + daisyUI 5 via `symfonycasts/tailwind-bundle` (standalone binary, version pinned in `config/packages/symfonycasts_tailwind.yaml`). `assets/styles/app.css` is the Tailwind input; the bundle serves the compiled output in its place, so it must be rebuilt: `make run` starts `tailwind:build --watch` as a Symfony CLI worker (`intranet/.symfony.local.yaml`), otherwise run `php bin/console tailwind:build`. Tailwind only scans the `@source` paths in `app.css` (templates, assets, src/Form); classes built dynamically elsewhere will not be generated. daisyUI is the vendored `assets/styles/daisyui*.mjs` plugin (excluded from AssetMapper); upgrade it by re-downloading those files. Forms use the custom theme `templates/form/daisyui_layout.html.twig`. Bootstrap Icons (`bi bi-*`) still come from a CDN; Bootstrap itself is gone. For production run `tailwind:build --minify` before `asset-map:compile`.

Interactivity is plain JS in `assets/app.js` using a delegated `live(selector, event, cb)` helper (`assets/js/event.js`) and axios, not Stimulus controllers; handlers use `this` (the matched element), not `event.target`. Like/comment endpoints return rendered Twig partials (`templates/timeline/_*.html.twig`) that `app.js` swaps into the DOM, keyed on CSS classes (`a.post-like`, `a.comment-like`, `a.add-comment`, `.comment-form form`, `.post`, `.comment`, `.comments`). Keep those classes and the partials' root elements in sync with `app.js` when editing templates (e.g. `.post` must stay the direct child of `_post.html.twig`'s root). Links with a `data-info-modal` attribute load their `href` into the shared `<dialog id="info-modal">` in `base.html.twig`; the user menu is a daisyUI drawer toggled by the `#user-drawer` checkbox.

**Images.** Profile photos are uploaded with VichUploader (mapping `users`, stored in `public/upload/users`) and resized with LiipImagine filter sets `user`, `user_post`, `user_comment` via Twig `vich_uploader_asset(profile, 'imageFile')|imagine_filter(...)`. Guard on `profile.imageName` before applying the filter: a null path throws.
