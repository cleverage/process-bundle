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

```bash
composer require cleverage/process-bundle
```

## Process definition

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

## Command line usage

CLI
- list
- help
- execute

```bash
$ ./bin/console cleverage:process:list
There are 1 process configurations defined :
 - project_prefix.process_name with 3 tasks
```

```bash
$ ./bin/console cleverage:process:help project_prefix.process_name
The process project_prefix.process_name contains the following tasks:
■ extract
│ 
■ transform
│ 
■ load
```

```bash
$ ./bin/console cleverage:process:execute project_prefix.process_name
Starting process 'project_prefix.process_name'...
DEBUG from project_prefix.process_name::load
array:2 [
  "id" => 123
  "slug" => "123-Test1-Test2"
]
Process 'project_prefix.process_name' executed successfully
```

Cron jobs & logs
