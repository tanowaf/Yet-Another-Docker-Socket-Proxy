- Firewall
  - matching requests/responses
    - see all ToDos in upstream
	- urls: accommodate the /vXXX/ prefix transparently
	- urls: accommodate ``?aaa` and ``#bbb` transparently by default
	  Test the limits of what docker server answers on its own
	    version?a#b/c no
	    version?a#b yes
	    version/c no
	- docker tags (how ?)
    - review: GET /containers/{id}/attach/ws allows to attach to a container stdin ? Is that RO ?
    - make sure we can replicate all the haproxy rules in NC-AIO haproxy.cfg
  - test the support for `tcp:/` sockets
  - look at all cases mentioned at https://hackviser.com/tactics/pentesting/services/docker

- add config examples and/or a config generator for common usecases, eg. 'all readonly', 'redact secrets', etc...

- proxy.php
  - catch exceptions, log them and return valid json?
    - test what happens with frankenphp by default with log_errors=on, error_Log=null -> one line per exception in the FP log
    - what about when in worker mode?

- test worker mode, incl. exceptions

- make favicon background transparent (or rounded)

- container build
  - run it on gha

- single-executable build

- add a testsuite
  - set up docker, make it listen on a tcp port; set up yadsp on a separate port
  - test differences in responses to both docker cli commands and synthetic requests
  - run it on gha
  - look for existing test suites, ex for https://github.com/fussybeaver/bollard, https://github.com/denibertovic/docker-hs,
    https://github.com/clue/reactphp-docker, https://github.com/moby/moby/tree/master/client

- allow fine-tuning resource usage? timeouts, maxconn, etc... (It's probably already possible via Caddy env vars. Test it...)
- allow end users to inject
  - matchers
  - filters
  via env vars + mounting dirs from the host

- explore the possibility of running this as docker-daemon authz plugin - see https://docs.docker.com/engine/extend/plugins_authorization/
  the main limitations seem to be:
  - the fact that the contents of "streaming responses" will not be forwarded to the daemin for inspection
  - the fact that there is no possibility to filter the daemon response - only to authorize or deny it
