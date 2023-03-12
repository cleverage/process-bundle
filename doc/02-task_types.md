Task types
==========

## Task definition

Tasks are symfony services that implements `CleverAge\ProcessBundle\Model\TaskInterface`.

Most of them takes an input to produce an output, but others might download a file, write a CSV, load database data...
Step by step, tasks are chained together according to the process workflow definition.

A task can be executed one or multiple times.

## Iterable tasks

In most of process, when loading a file or querying database, you want to manipulate a collection of data. If you're 
managing huge amount of data you might face memory issues.

Iterable tasks are a way to resolve this issue. They implement `CleverAge\ProcessBundle\Model\IterableTaskInterface`.

Every time it's needed they will dispatch a new chunk of data to the next task (cascading to other tasks), until all 
data have been processed. 

The main condition to use them is to have no interaction between each chunk of data. If it's not the case, you might 
have to look for another way to organize your data, in order to find bigger chunks. 

A few examples where iterable are useful:
- You load a collection of database entities: for each of them you want to edit a field and save it back in database.
- In a model with 2 types A and B, where A contains a collection of B: you want to export every B entity that match a 
condition on itself and on its parent. You can iterate on A entities, then if it match the first condition, iterate on B 
entities, check the second condition and finally export them.

## Blocking tasks

Once you produced an iterated flow of data, there can be some point where you need to get the whole result to do a 
onetime operation.

Blocking tasks aims to provide a way to block the flow, waiting for all preceding task to complete. They implement 
`CleverAge\ProcessBundle\Model\BlockingTaskInterface`.

The main category of blocking task is aggregator tasks: they accumulate data until execution. Yet one huge caveat is 
they can provoke memory issues (due from their very nature). A strong advice when using them is to control the uphill 
amount of data (either with a hard limit or by storing the minimum amount of data). Some examples:
- After loading a collection of entity from database, you can iterate on them to extract and transform some values, 
before finally doing a onetime upload of the result as a JSON.
- Once you retrieved some collection of data, you want to check for a global condition such as "there is exactly `XX` 
data that fulfill `YY` condition on one field". In this case, instead of storing the full data, you could only store the
field values.

Other blocking tasks might be accumulators: with each input they change some internal data (value, file, ...) without 
storing a huge collection. Once there is no input, only the final data is outputted. 
The simplest example is a counter, but it can also be a CSV writer.

For now, due to model limitations, a task cannot be blocking and iterable.

## Initializable tasks

Some tasks may have mandatory initial actions. It may be opening a connexion to a remote server, testing if file 
permissions are ok, ... But in most of those case you want to check those setup before actually starting the process.

Initializable tasks, which implements `CleverAge\ProcessBundle\Model\InitializableTaskInterface`, can setup, check 
and prepare anything needed for the main execution of the task.

This is especially useful (for example) when the process starts with heavy tasks before actually uploading a file. If 
the remote server cannot be reached the process can fail at the initialization step, and not at the end of the process.

## Configurable tasks and options

Most tasks aims to have a generic behavior. This can provide reusablility but every times it needs a slightly different 
behavior. Options are a way to configure a task.

Configurable tasks extends `CleverAge\ProcessBundle\Model\AbstractConfigurableTask`. As you might notice it's an 
initializable task that rely on
[Symfony's OptionsResolver Component](https://symfony.com/doc/current/components/options_resolver.html). This allows to 
check the definition of the options before actually executing the process.

_TODO_ more about definition in XXX section

## Transformers

Transformers are a special subset of this bundle. They're not tasks strictly speaking, but used by them. The main entry 
point for Transformers is the `CleverAge\ProcessBundle\Task\TransformerTask`, whose only purpose is to take some input, 
pass it to a transformer and transfer the output to next task.

The idea is to allow a great flexibility (especially using the [MappingTransformer]()), without using too much code.

They implement `CleverAge\ProcessBundle\Transformer\TransformerInterface` or 
`CleverAge\ProcessBundle\Transformer\ConfigurableTransformerInterface`.

## Flushable tasks

_TODO_

## Finalizable tasks

On the opposite, some tasks may require cleanup work at the very end of the process (e.g. cleanup a temporary folder).

Finalizable tasks implements `CleverAge\ProcessBundle\Model\FinalizableTaskInterface` and can trigger any work at the 
very end of a process.

_TODO_ check behavior during failed process
