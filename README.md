# TanoWAF - Yet Another Docker Socket Proxy

Yet another security-enhancing proxy for the Docker Socket.

Aka. a small forward proxy for filtering the calls to the Docker API to only what you want to expose.

It is a "level 7" proxy, in that it is aware of the semantics of the Docker API protocol, and it allows both filtering
the Requests and rewriting the Responses.

## Why ?

"Giving access to your Docker socket could mean giving root access to your host, or even to your whole swarm, but some
services require hooking into that socket to react to events, etc. Using a proxy lets you block anything you consider
those services should not do." (docker-socket-proxy readme)

There are multiple, mature Docker Socket Proxy projects already.

What this one does different is that it allows both filtering the Requests and rewriting the Responses.

This means f.e. restricting a client to
- execute a call to GET /containers/json and be able to see only a specific list of containers
- inspect/act upon only a specific set of containers

It was inspired by https://blog.foxxmd.dev/posts/restricting-socket-proxy-by-container/

## WORK IN PROGRESS !!!

See [Roadmap.md] for features not yet implemented

## Installation

### As Docker Container

There are no images produced yet.

For now:
1. `git clone` this project
2. run
   ```shell
   sudo docker run \
       --name yadsp \
       --rm \
       -p 0.0.0.0:2375:2375 \
       -v .:/app \
       -v ./docker/Caddyfile:/etc/frankenphp/Caddyfile:ro \
       -v /var/run/docker.sock:/var/run/docker.sock:ro \
       -e YADSP_CONFIG='{"allow version info":{"url": "/version"}}' \
       dunglas/frankenphp
   ```
3. set up dependencies, using composer, within the container. In a 2nd terminal, run:
  ```shell
  sudo docker exec yadsp php -r 'chdir("/tmp"); if (!copy("https://getcomposer.org/installer", "composer-setup.php")) exit(2); if (hash_file("sha384", "composer-setup.php") === "c8b085408188070d5f52bcfe4ecfbee5f727afa458b2573b8eaaf77b3419b0bf2768dc67c86944da1544f06fa544fd47") { echo "Installer verified".PHP_EOL; } else { echo "Installer corrupt".PHP_EOL; unlink("composer-setup.php"); exit(1); } exec("php composer-setup.php"); unlink("composer-setup.php");' && \
  sudo docker exec yadsp /tmp/composer.phar install --classmap-authoritative && \
  sudo docker exec yadsp rm /tmp/composer.phar
  ```
4. run `export DOCKER_HOST=tcp://127.0.0.1:2375`
5. test: `sudo --preserve-env=DOCKER_HOST docker ps` - this should not show any containers, despite the fact that there is one running,
   whereas `sudo --preserve-env=DOCKER_HOST docker version` should show the Server information

### With FrankenPHP
...
### With another webserver
...

## Configuration

The configuration can be passed in to the container as a string, via environment variable `YADSP_CONFIG`, or via a file.
The file can be mounted into the container via `-v`, and its location specified via env var `YADSP_CONFIG_FILE`, eg:
```shell
    -e YADSP_CONFIG_FILE=/tmp/yadsp_fw_config.json \
    -v ./config.json:/tmp/yadsp_fw_config.json:ro \
```

The format to be used in both cases is Json.

The configuration format is:
- a json object, where each element is a rule (the names of rules are not important - they are mostly of use to debug configuration errors)
- every rule is a json object, each element of which is a match condition
- a match condition is made of a key, being the type of match, and of a value, which is generally a single string,
  or an array of strings, in which case any of the strings is considered valid.

The logic applied is:
- by default the firewall only allows access to the docker and '/ping' api call, everything else gets a 404 response
- the rules are evaluated in the order that they are defined
- for each rule, all its match conditions are verified
- if all the conditions of the rule match, the request is allowed to go through

The supported match conditions are:
- `client_address` - a string or array of strings. IPv4 only for now. '*' wildcards supported
- `client_port` - an integer or array of integers.
- `http_method` - a string or array of strings. Only exact matching. Eg: `HEAD`, `GET`, ...
- `url` - a string or array of strings. WIP...
- `user_agent` - a string or array of strings. '*' wildcards supported

## Example rule sets

NB: see the [Docker API reference](https://docs.docker.com/reference/api/engine) for details about which requests to whitelist.

### Allow full access, read-only

```json
{"read-only access": {"http_method":  ["GET", "HEAD"]}}
```

This relies on the Docker API following RESTful semantics.

## Design principles

1. Security first. No requests are allowed by default, everything has to be whitelisted.
2. Ease of use. The users should not have to learn the equivalent of the Varnish VCL or HAProxy configuration language
   to achieve common scenarios. Error messages should be clear and rather verbose than cryptic. Logging facilities
   should be extensive.
3. Flexibility. The proxy should be easy to configure for common scenarios and extend to achieve uncommon ones
4. Performance. Maximum speed of execution and minimum cpu usage / memory usage are _important_ but not the main concern.
   Safety, robustness and flexibility come first.

Which translates into:
- PHP 8.2 and up
- strict typing everywhere
- using DI patterns as much as possible
- using the psr-7, psr-15, psr-18 interfaces means it should be easy to extend/embed the Proxy class in other middlewares
- avoid relying on too many dependencies - f.e. no Monolog, Symfony ConfigTreeBuilder
- delegate all possible processing to the 'bootstrap' phase, so that the processing loop can be as efficient as possible

## FAQ

...
