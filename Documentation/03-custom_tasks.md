Custom tasks
============

Once you've worked with provided tasks to build simple processes, you may face cases where you want to build a more 
complex workflow. Common tasks would not be powerful enough (or would imply much unoptimized setup), so you'll have to 
create your own.

## Service declaration

As stated before, Tasks are simple services implementing `CleverAge\ProcessBundle\Model\TaskInterface`, so you can 
design it like any other service, with only small specificities.

Most of the time you don't want that a task declared in a process shares some data with another declaration, using the 
same service, or even with another instance of your process. Since a task is a service, by default, symfony will only 
create one instance (keeping values in every attribute of your class). You can force it to create one instance by 
process by using the option [`shared: false`](https://symfony.com/doc/current/service_container/shared.html).

## Using the state object

With the `TaskInterface::execute` method comes a small container object : the State 
(`CleverAge\ProcessBundle\Model\ProcessState`).

It's the only way to interact with the rest of the process. Each time the task will need to process data, the `execute`
method will be called and the `$state` will contain a new input (`ProcessState::getInput`). Once the task is done, it 
may pass a new output to the next task (`ProcessState::setOutput`).

The State also provide reporting tools:
* `ProcessState::log`: register a new log message (see[logging]())
* `ProcessState::getConsoleOutput`: direct link to Symfony's Console Output (deprecated, prefer log)

Sometimes, when you execute a task, you need to change how the process may continue. It will be detailed in depth in 
the [next chapter about error management]() but here are the main methods
* `ProcessState::setSkipped`: process won't continue to next step
* `ProcessState::setStopped`: process will fully stop
* `ProcessState::setErrorOutput`: allow to direct an output to an error branch from your workflow

## Options

To reuse more easily tasks, the best way is to use options. A basic option management implementation is already 
available in `CleverAge\ProcessBundle\Model\AbstractConfigurableTask`.

Based on [Symfony's OptionsResolver Component](https://symfony.com/doc/current/components/options_resolver.html) this
abstract allows you to override its `configureOptions` method to add your requirements, default values and normalizer.
It's a very important step to allow manipulating your custom task. Even when you may have only one instance, and one 
purpose, you'll find that having some options will help you debug a situation, or evolve your process.

## Iterable and Blocking tasks implementations

Defining your tasks as Iterable or Blocking is as simple as implementing one of corresponding interface:
* `CleverAge\ProcessBundle\Model\IterableTaskInterface`: the `next` method should behave almost the same as PHP's native
[next](https://secure.php.net/manual/en/function.next.php) function for arrays (except it only returns a boolean) 
* `CleverAge\ProcessBundle\Model\BlockingTaskInterface`: every `execute` method call should only accumulate data from 
the input and once every previous task is _resolved_, the `proceed` method should provide an output (see [TODO]() for 
the exact definition of a resolved method)

It's up to you to know when you should be using one of those, but basically:
* When you loop over a collection of independent elements, you should use an Iterable task. It may help you reduce the 
memory footprint.
* When you need to collect, upload, ... data as a whole, then you might need a Blocking task. Be sure to read [previous 
chapter's notice]() about performance.

Tasks cannot be both Iterable and Blocking.

## Transformers

Transformer are another kind of service. They implement `CleverAge\ProcessBundle\Transformer\TransformerInterface` or 
`CleverAge\ProcessBundle\Transformer\ConfigurableTransformerInterface` and are declared with a `cleverage.transformer`
tag.

They're meant to be lightweight, composable, and stateless piece of your process. Be free to implement custom one as 
soon as provided one doesn't fit your goal.

Once properly setup, they should be accessible from the `CleverAge\ProcessBundle\Registry\TransformerRegistry` (already 
available in the `TransformerTask`), using the code from `TransformerInterface::getCode`. When creating a new transformer
for your project you should use an internal prefix in your codes to avoid conflict with potential standard additions.
 
Just as tasks, options can be managed with 
[Symfony's OptionsResolver Component](https://symfony.com/doc/current/components/options_resolver.html), so be sure to 
implement a few of them.

## Logging

_TODO_

A logging chanel has been defined for tasks 

```yaml
        tags:
            - { name: monolog.logger, channel: cleverage_process_task }
```
