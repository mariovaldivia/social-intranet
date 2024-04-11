# Social Intranet


Built with PHP8.2, Symfony7 using Docker containers.

### Clone repository
```sh
git clone https://github.com/mariovaldivia/social-intranet.git
```

### Build docker containers
```sh
make build
```

### Start docker containers
```sh
make start
```

### Access container shell
```sh
make ssh-be
```

### Install dependencies
```sh
composer install
```

### Migrate database
```sh
php bin/console make:migration
php bin/console doctrine:migrations:migrate
```

### Load fixtures
```sh
php bin/console doctrine:fixtures:load
```

### Init web server
```sh
symfony serve
```

### Access web app from a web browser with the following URL
http://localhost:1000

