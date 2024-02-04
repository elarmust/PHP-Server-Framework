# PHP Server Framework
A lightweight PHP server framework powered by Swoole for creating HTTP/WebSocket servers.

**Note: This project is currently under development, and some features may not work as expected.
Feel free to explore and contribute, but be aware that the codebase is subject to changes.**

# Main features
+ HTTP router
+ MVC
+ WebSocket
+ Database migrations
+ Event system
+ Task scheduling
+ CLI
+ Logging
+ Configuration system

# General requirements
+ MySQL database
+ Docker
+ WSL is required when running on Windows

# Installation via Docker

Start by cloning the project with

```
git clone https://github.com/elarmust/PHP-Server-Framework.git
```

Copy docker-examples to docker.
```
cp docker-examples/* docker/
```
You may modify the Dockerfile and docker-compose.yml according to your needs.

Before the Docker container is started, rename config-example.json to config.json and edit config.json with valid MySQL connection information.
If you've renamed any of the containers, be sure to reflect these changes in the following commands.
<br />
Start the Docker containers and run basic migrations with

```
cd docker
docker compose up -d
docker attach framework-framework-1
migrate run up all
```

You can access the built-in CLI tool with
```
docker attach framework-framework-1
```

# Contributing
If you'd like to contribute, you can do the following:

+ Create a fork and submit a pull request or
+ [Submit an issue or feature request](https://github.com/elarike12/PHP_Framework/issues)

# TO DO list
+ Add localization feature.
+ Cron improvement
+ Add command arguments to unit tests and fix some errors
+ Better CLI and fix an error thrown, when invalid command is used.
+ XML and YML configuration support maybe.
