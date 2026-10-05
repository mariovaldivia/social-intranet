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

**Companies.** `Company` (legal name, trade name, unique tax ID/RUT, industry, contact, address, ISO country code, active flag, timestamps) is managed only from EasyAdmin (`CompanyCrudController`). A company has many `Site` (offices, branches, work sites/"faenas", plants, warehouses; type is the `App\Enum\SiteType` backed enum, which implements `TranslatableInterface` so EasyAdmin shows `site.type.*` labels; optional `code` unique per company), managed in `SiteCrudController`; deleting a company deletes its sites. A site has many `SiteActivity` (work done there: date-only `date`, `ActivityType` (technical visit, maintenance, installation, repair, inspection, emergency, training, other), `ActivityStatus` (scheduled by default, in progress, completed, cancelled), description, many-to-many `assignedUsers`, `createdBy` set in `SiteActivityCrudController::persistEntity`), deleted with their site. EasyAdmin matches translatable enum choices by case name (e.g. `renderAsBadges` keys are `ActivityStatus::Scheduled->name`); autocomplete fields render as a compound `[autocomplete]` child, which matters in tests. Users see their own assignments outside the admin in `MyActivitiesController` (`/my-activities`: overdue = past date still scheduled/in progress, then upcoming grouped by day without cancelled ones; `/my-activities/history`), backed by `SiteActivityRepository::find{Upcoming,Overdue,Past}ForUser()` with "today" from the clock. Planning happens in `PlanningController` (`/planning`, requires `ROLE_PLANNER`, which `ROLE_ADMIN` inherits through `role_hierarchy`): a server-rendered Monday-to-Sunday month grid (`?month=YYYY-MM`, filters `company`/`site`/`user` auto-submitted by the `auto-submit` Stimulus controller) with an agenda list on small screens; the "+" of a day opens `/planning/new?date=...` (and `site=` when filtered) using `SiteActivityType`. Calendar entries and "My activities" cards open the detail page `ActivityController` (`/activities/{id}`); `SiteActivityVoter` decides `ACTIVITY_VIEW` (planners and assigned users) and `ACTIVITY_EDIT` (planners, shows the Update button). Saving an edit returns to the detail; deleting returns to the calendar month. Activity icons and status colors live in `templates/macros/activity.html.twig`. To give someone planning access without admin, add `ROLE_PLANNER` to their `user.roles`.

**Photo gallery.** `PhotoAlbum` (title, description, date-only `eventDate`, `createdBy`) has many `Photo` (VichUploader mapping `gallery` → `public/upload/gallery`, caption, `uploadedBy`); the cover is the first photo. `GalleryController` (`/gallery`) lists albums, creates them and uploads several photos at once through `PhotoUploadType` (unmapped multiple `FileType`, one `Photo` per file; 10 MB/20 files, matching `docker/uploads.ini`). `GalleryVoter` (`GALLERY_DELETE`): admins, the album creator (album and its photos) and a photo's uploader may delete. Each upload creates a timeline post; `RemoveEmptyGalleryPostListener` deletes that post when its last photo is deleted, and deleting an album cascades to its posts. Thumbnails use the `gallery_thumb`/`gallery_large` Liip filters and the `lightbox` Stimulus controller shows full-size photos. `RemoveImageThumbnailsListener` deletes Liip cached thumbnails when Vich removes a file (gallery and profile photos); add new mappings/filters to its map. In the test env the `gallery` mapping writes to `var/test/upload/gallery`; tests that create albums must clean up through the ORM (`LogsInUserTrait::removeTestUserContent`) so files are deleted.

**Timeline.** `TimelineController` serves the timeline, user walls (`/wall/{username}`), comments and likes. A `Post` has a user message, or is linked to an `Event` (`EventController::new` calls `TimelineService::addEvent()`), or announces a gallery upload (`album` plus the uploaded `photos`, created by `TimelineService::addPhotos()` on every upload, rendered by `_postAlbum.html.twig`); the last two have no message. Because of that, `Post::$message` is nullable and the "not blank" rule lives on `PostType`, not the entity. `Like` targets either a post or a comment; `Post::$likes`, `Post::$comments` and `Comment::$likes` cascade on remove, so deleting a post (or an event/album that owns it) removes its likes and comments. Timestamps (`date`, `createdAt`) are set in `#[ORM\PrePersist]` callbacks on the entities.

**Frontend.** AssetMapper + importmap (no Node build). Styling is Tailwind CSS 4 + daisyUI 5 via `symfonycasts/tailwind-bundle` (standalone binary, version pinned in `config/packages/symfonycasts_tailwind.yaml`). `assets/styles/app.css` is the Tailwind input; the bundle serves the compiled output in its place, so it must be rebuilt: `make run` starts `tailwind:build --watch` as a Symfony CLI worker (`intranet/.symfony.local.yaml`), otherwise run `php bin/console tailwind:build`. Tailwind only scans the `@source` paths in `app.css` (templates, assets, src/Form); classes built dynamically elsewhere will not be generated. daisyUI is the vendored `assets/styles/daisyui*.mjs` plugin (excluded from AssetMapper); upgrade it by re-downloading those files. Forms use the custom theme `templates/form/daisyui_layout.html.twig`. Bootstrap Icons (`bi bi-*`) still come from a CDN; Bootstrap itself is gone. For production run `tailwind:build --minify` before `asset-map:compile`.

Interactivity is Stimulus controllers in `assets/controllers/` (auto-registered by `assets/bootstrap.js`; Turbo Drive is also on) using `fetch`; there is no axios or jQuery. Server endpoints return rendered Twig partials:
- `replace` swaps its element with the HTML from the clicked link (`data-action="replace#load"`): `like_post` returns `_postActions.html.twig` and `like_comment` returns `_commentItem.html.twig`, both with `data-controller="replace"` on their root so the new element keeps working.
- `comments` (on each post's `.card-body`, also on `/event/`) loads the form into its `form` target and submits it; `post_comment` answers 200 with the new comment (appended to the `list` target) or 422 with the form and its errors. The thread is `_comments.html.twig` (oldest first, via `#[ORM\OrderBy]` on `Post::$comments`).
- `submit-on-enter` on the message textareas (`_messageBox.html.twig`): Enter submits, Shift+Enter adds a line break.
- `info-modal` on `<body>` loads a link's `href` into the shared `<dialog>` (`data-action="info-modal#open"`, titled with the link's `title`).

When editing these partials keep the `data-controller`/`data-action`/`data-*-target` attributes; the CSS classes (`.post-actions`, `.comment`, ...) are only used as test selectors. Comment dates use the `time_ago` Twig filter (`src/Twig/AppExtension.php`, strings in `translations/messages+intl-icu.en.yaml`): relative up to 6 days, then the absolute date. The user menu is a daisyUI drawer toggled by the `#user-drawer` checkbox.

**Time zones.** Dates are stored in UTC (PHP's default time zone, never change it: existing rows are UTC). `APP_TIMEZONE` (default `America/Santiago` in `config/services.yaml`; docker-compose sets it from the root `.env` `TIMEZONE`) is used for display and for "today": Twig's `|date` converts to it, and the autowired `Psr\Clock\ClockInterface` returns `now()` in it (`config/services.yaml`), so queries take "today" from the clock (`EventRepository::findUpcoming/findPast`, `ProfileRepository::nextBirthdays/latestHires`) instead of MySQL's `CURRENT_DATE()`. Date-only columns (`Event::$date`, `Profile::$birthdate`, `Profile::$hireDate`) must be rendered with `|date(format, false)`, or they show the previous day.

**Translations.** All UI text uses translation keys, never literal strings: `{{ 'event.list.title'|trans }}` in templates, `'label' => 'profile.field.name'` in form types, and constraint messages like `NotBlank(message: 'post.message.not_blank')`. Strings live in `translations/messages+intl-icu.{en,es}.yaml` (keys grouped by section: `app`, `nav`, `common`, `login`, `timeline`, `post`, `comment`, `like`, `event`, `profile`, `time_ago`; ICU placeholders like `{date}`) and `translations/validators.{en,es}.yaml` (plain format, not ICU: validator messages use `{{ limit }}` placeholders). Add every new key to both languages: `TranslationsTest` fails otherwise. The UI language is `APP_LOCALE` (`es` by default, or `en`; English is the fallback for missing keys); the test env is always `en`. `php bin/console debug:translation es --only-missing` lists keys used in templates but not defined (keys chosen with a ternary or passed as variables are reported as unused, that is expected). Text inside JS controllers comes from the template via Stimulus values (e.g. `data-info-modal-error-value`). Dates still use PHP formats, so month names (`d M`) are English in both languages.

**Images.** Profile photos are uploaded with VichUploader (mapping `users`, stored in `public/upload/users`) and resized with LiipImagine filter sets `user`, `user_post`, `user_comment` via Twig `vich_uploader_asset(profile, 'imageFile')|imagine_filter(...)`. Guard on `profile.imageName` before applying the filter: a null path throws.
