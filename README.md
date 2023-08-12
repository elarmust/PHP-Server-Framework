# PHP Server Framework
A lightweight PHP server framework powered by Swoole for creating HTTP/WebSocket servers.

# Main features
+ HTTP routing
+ MVC
+ WebSocket
+ Database migrations
+ Event system
+ Cron like scheduling
+ CLI commands
+ Logging
+ Configuration system

# Documentation
[Wiki page](https://github.com/elarike12/PHP_Framework/wiki)


# General requirements
+ MySQL database
+ Docker
+ WSL is required when running on Windows

# Installation via Docker
```
cd docker
docker-compose build framework
docker-compose create framework
```
Before the Docker container is started, please edit config.json with valid MySQL connection information.
You can start the framework server with

```
docker start -ai docker-framework-1
```

# Contributing
If you'd like to contribute, you can do the following:

+ Create a fork and submit a pull request or
+ [Submit an issue or feature request](https://github.com/elarike12/PHP_Framework/issues)
