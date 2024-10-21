Memory usage analysis
=====================

This method is clearly not the most elegant one but it doesn't require any special tool apart from Gnuplot on your
desktop environment.

Add this to your process:
```yaml
memory:
    service: '@CleverAge\ProcessBundle\Task\TransformerTask'
    options:
        transformers:
            mapping:
                mapping:
                    memory_usage:
                        set_null: true
                        transformers:
                            callback:
                                callback: memory_get_usage
    outputs: [write_memory]

write_memory:
    service: '@CleverAge\ProcessBundle\Task\File\Csv\CsvWriterTask'
    options:
        file_path: '%kernel.project_dir%/memory.dat'
        headers:
            - memory_usage
```

Then just output regularly to the ```memory``` and this will write the memory usage to the ```memory.dat``` at the root
of your project.

Then launch your process using the production environment (you can't rely on the development environment memory wise).

To graph the output of this process, use this Gnuplot command in your host environment:
(not in a container because Gnuplot uses the X server to output the window containing the graph)

```bash
$ gnuplot -e 'while(1) {plot "memory.dat" using 0:1 with lines; pause 1; reread}'
```
