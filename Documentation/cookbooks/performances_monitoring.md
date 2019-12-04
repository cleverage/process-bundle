Performances Monitoring
=======================

For heavy work there is multiple solutions to improve speed (_TODO add link to multithreading cookbook_) and memory consumption.

While developing custom tasks you might want to see how well your PHP code behaves, and one solution is to use [Blackfire](https://blackfire.io)
to analyse call graphs with timings & memory analysis.

## Setup

If you're using the official PHP docker image, you can add the Blackfire probe to your container ([Official documentation](https://blackfire.io/docs/up-and-running/installation)) :

```dockerfile
ARG BLACKFIRE_PHP_VERSION=71
ARG BLACKFIRE_PROBE_VERSION=1.29.1
ARG BLACKFIRE_AGENT_VERSION=1.30.0

# Blackfire PHP Probe
RUN curl -o $(php -i | grep -P "^extension_dir " | sed "s/^.* => //g")/blackfire.so -D - -L -s https://packages.blackfire.io/binaries/blackfire-php/${BLACKFIRE_PROBE_VERSION}/blackfire-php-linux_amd64-php-${BLACKFIRE_PHP_VERSION}.so
RUN docker-php-ext-enable blackfire
# Blackfire Agent (for HTTP calls)
RUN curl -o /usr/bin/blackfire-agent -L https://packages.blackfire.io/binaries/blackfire-agent/${BLACKFIRE_AGENT_VERSION}/blackfire-agent-linux_amd64
RUN chmod +x /usr/bin/blackfire-agent
# Blackfire CLI (for console)
RUN curl -o /usr/bin/blackfire -L https://packages.blackfire.io/binaries/blackfire-agent/${BLACKFIRE_AGENT_VERSION}/blackfire-cli-linux_amd64
RUN chmod +x /usr/bin/blackfire
```

Then, with your [own credentials](https://blackfire.io/my/settings/credentials), execute inside your container
```shell script
blackfire config --client-id=$CLIENT_ID --client-token=$CLIENT_TOKEN
```

You can also pass those ids during the run call.

## Usage

Just prefix `blackfire run` while executing your PHP commands inside your container, and Blackfire will provide you an URL
with the resulting call graph. Be careful to cleanup the cache before calling Blackfire, to avoid any noise.

```shell script
$ ./bin/console c:c --env=test

 // Clearing the cache for the test environment with debug true                                                         

                                                                                                                        
 [OK] Cache for the "test" environment (debug=true) was successfully cleared.                                           
                                                                                                                        

$ blackfire --client-id=xxx-xxx-xxx-xxx-xxx --client-token=xxxxxxxxxxx run php bin/console --env=test cleverage:process:execute test.simple_process
Starting process 'test.simple_process'...
Process 'test.simple_process' executed successfully

Blackfire Run completed
Graph URL https://blackfire.io/profiles/xxx-xxx-xxx-xxx-xxx/graph
No tests! Create some now https://blackfire.io/docs/cookbooks/tests
No recommendations

Wall Time     102ms
I/O Wait        n/a
CPU Time        n/a
Memory       5.35MB
Network         n/a     n/a     n/a
SQL             n/a     n/a
``` 
