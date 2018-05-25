Quick start
===========

## Base concepts

In most application, there's always a set of workflows defining how to manage your data. It can be imports/exports, 
asynchronous treatments or periodically checking an API... With its life, it may grow, code may duplicate quite quickly.

This bundle aims to provide a framework to build efficient, quick to build, easy to change workflows.

Its main concept is the *process*: it's a set of *tasks* chained together according to the workflow you want
to define. Each *task* has the duty to perform one simple action on each piece of *data* provided.

The most common example is the ETL. It's a kind of application whose main purpose is to
- *Extract* a chunk of data from a source (database, file, API, ...)
- *Transform* this data into something else (modify the values, change the format, compute some statistics, ...)
- *Load* the transformed data into a destination (another database, file, API, ...)

## Installation

This bundle requires Symfony 3 and Doctrine. You can install it using composer:

```bash
composer require cleverage/process-bundle
```

## Process definition

Most of the work is done through the bundle configuration. 

Under `clever_age_process.configurations` you can add processes, and for each process define a set of `tasks`.
The most basic configuration for a process is:
```yaml
clever_age_process:
    configurations:
        <process_name>:
            tasks: []
```

Then you can add tasks in this array. They consist of a `service`, optionally configured by `options`, and eventually
 chained with others through their `outputs`. Minimal syntax is:
```yaml
<task_name>:
    service: <service_reference>
    options: 
        <option_key_1>: <option_value_1>
        <option_key_2>: <option_value_2>
        <option_key_3>: <option_value_3>
    outputs: [<next_task_name_1>, <next_task_name_2>, <next_task_name_3>]
```

Below you can see a minimal working ETL example. It consist of 3 tasks:
- the first *extract* some data (the [constant output task]() outputs... a constant value): it's an array with 3 
keys/values
- the second *transform* the given value (the [transformer task]() is one of the most important!): the output is then an 
array with 2 keys/values, created using the value from previous task
- finally, the last will just display the result (it's a cheap *load*, using the [debug task](), only for development 
purpose!)

```yaml
clever_age_process:
    configurations:
        project_prefix.process_name:
            tasks:
                extract:
                    service: '@cleverage_process.task.constant_output'
                    options:
                        output:
                            id: 123
                            firstname: Test1
                            lastname: Test2
                    outputs: [transform]

                transform:
                    service: '@cleverage_process.task.transformer'
                    options:
                        mapping:
                            id:
                                code: '[id]'
                            slug:
                                code:
                                    - id
                                    - firstname
                                    - lastname
                                transformers:
                                    implode:
                                        separator: '-'
                    outputs: [load]

                load:
                    service: '@cleverage_process.task.debug'
```

There is more to know about process configuration. See [the full process configuration reference]().

## Command line usage

Once your process are defined, you want to use them. Some console commands are provided for their manipulation:
- `cleverage:process:list`: gives you a list of all defined process
- `cleverage:process:help <process_code>`: tries to show you what's inside `<process_code>` using a nice charting
- `cleverage:process:execute <process_code_1> [<process_code_2> ...]`: starts one by one `<process_code_1>`, 
`<process_code_2>`, ... , unrolling tasks one by one. Note that you can use verbosity options (`-v`, `-vv`, `-vvvv`) 
to look in depth what's happening.

Applied to previous example, it will show:

```
$ ./bin/console cleverage:process:list
There are 1 process configurations defined :
 - project_prefix.process_name with 3 tasks
```

```
$ ./bin/console cleverage:process:help project_prefix.process_name
The process project_prefix.process_name contains the following tasks:
■ extract
│ 
■ transform
│ 
■ load
```

```
$ ./bin/console cleverage:process:execute project_prefix.process_name
Starting process 'project_prefix.process_name'...
DEBUG from project_prefix.process_name::load
array:2 [
  "id" => 123
  "slug" => "123-Test1-Test2"
]
Process 'project_prefix.process_name' executed successfully
```

## Automation

Once everything is working fine, you may want to automate your processes. The standard way is using the Unix cron jobs:
```
# Every two hours, execute <my_process>
0 */2 * * * ./bin/console cleverage:process:execute <my_process>
```

To check if everything went fine, logs are stored in database:
- `clever_process_history`: logs process started, with `process_code`, `start_date`, `end_date` and `statut`
- `clever_task_history`: logs custom tasks logs (see [logging]()), with `task_code`, `message`, `logged_at` date, `level`, a `reference` and `context`
