Memory usage analysis
=====================

This method is clearly not the most elegant one but it doesn't require any special tool apart from Gnuplot on your
desktop environment.

Add these tasks to your process:
```yaml
memory:
    service: '@CleverAge\ProcessBundle\Task\TransformerTask'
    options:
        transformers:
            mapping:
                mapping:
                    memory_usage:
                        constant: false # Argument of memory_get_usage(), the input is ignored
                        transformers:
                            callback:
                                callback: memory_get_usage
    outputs: [write_memory]

write_memory:
    service: '@CleverAge\ProcessBundle\Task\File\Csv\CsvWriterTask'
    options:
        file_path: '%kernel.project_dir%/memory.dat'
        headers: [memory_usage]
        write_headers: false
```

Then add `memory` to the `outputs` of the task you want to monitor (e.g. the one reading your data): each time this
task produces an output, the current memory usage is written to the `memory.dat` file at the root of your project.

Then launch your process using the production environment (you can't rely on the development environment memory wise).

To graph the output of this process, use this Gnuplot command in your host environment
(not in a container because Gnuplot uses the X server to output the window containing the graph):

```bash
$ gnuplot -e 'while(1) {plot "memory.dat" using 0:1 with lines; pause 1; reread}'
```

Alternative: log memory per process phase
-----------------------------------------

For a more granular analysis, measure the memory at several points of your process, with a constant `phase` column to
identify each of them. All measure tasks send their output to the same `write_memory` task:

```yaml
memory_after_read:
    service: '@CleverAge\ProcessBundle\Task\TransformerTask'
    options:
        transformers:
            mapping:
                mapping:
                    phase:
                        constant: after_read
                    memory_usage:
                        constant: false
                        transformers:
                            callback:
                                callback: memory_get_usage
    outputs: [write_memory]

memory_after_transform:
    service: '@CleverAge\ProcessBundle\Task\TransformerTask'
    options:
        transformers:
            mapping:
                mapping:
                    phase:
                        constant: after_transform
                    memory_usage:
                        constant: false
                        transformers:
                            callback:
                                callback: memory_get_usage
    outputs: [write_memory]

write_memory:
    service: '@CleverAge\ProcessBundle\Task\File\Csv\CsvWriterTask'
    options:
        file_path: '%kernel.project_dir%/memory_phase.csv'
        headers: [phase, memory_usage]
```

Add `memory_after_read` to the outputs of your reader task, and `memory_after_transform` to the outputs of your
transformer task, then compare the values of each phase in `memory_phase.csv`.
