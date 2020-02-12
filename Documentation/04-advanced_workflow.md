Advanced Workflow
=================

## Process execution flow

_TODO_ 
* task resolution & blocking
* wrapping execution in subprocess
* errors & skips
* orphan tasks

## Events

Events are being send around process execution (see `CleverAge\ProcessBundle\Event\ProcessEvent`) :
* `cleverage_process.start` : on process start
* `cleverage_process.end` : on successful process end
* `cleverage_process.fail` : on failed process end (with the associated error)

Another event is send when a process is executed with the CLI (see `CleverAge\ProcessBundle\Event\ConsoleProcessEvent`) :
* `cleverage_process.cli.init` : before executing any process, giving access to console Input/Output objects

You can also use [EventDispatcherTask](reference/tasks/event_dispatcher_task.md) to manually trigger an event in the middle of a process.

## Parallelization

_TODO_
* ProcessLauncherTask
* EnqueueBundle
* pthread
