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
Before the Docker container is started, please rename config-example.json to config.json and edit config.json with valid MySQL connection information.
You can start the framework server with

```
docker start -ai docker-framework-1
```

# Contributing
If you'd like to contribute, you can do the following:

+ Create a fork and submit a pull request or
+ [Submit an issue or feature request](https://github.com/elarike12/PHP_Framework/issues)

# TO DO list
+ Module system refactoring
+ Unit tests
+ Better CLI
+ XML and YML configuration support
+ Expand csrf functionality
