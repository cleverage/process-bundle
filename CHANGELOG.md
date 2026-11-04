Latest
------

## Changes
* [#444](https://github.com/cleverage/process-bundle/issues/444) New `exclusive` option of the processes (`false` by default): an execution of an exclusive process fails with the new `ProcessAlreadyRunningException` while another execution of it is running (same PHP process, or another one sharing the lock store), dispatched as any process failure. Backed by symfony/lock (new `suggest`, required by an exclusive process when the container is compiled): `ProcessManager` takes an optional `LockFactory` (`lock.factory`), the lock is named `cleverage_process.exclusive.<process_code>`, has a TTL of 300 seconds refreshed before the tasks, and is released at the end of the process or by the reset of the services. New `ProcessConfiguration::isExclusive()`. Update documentation, add tests.
* [#443](https://github.com/cleverage/process-bundle/issues/443) Documentation: two executions of the same process can run at the same time (cron, user interface, scheduler, Messenger workers); make the outputs unique and the writes idempotent, or lock the execution.
* [#440](https://github.com/cleverage/process-bundle/issues/440) Documentation: `json_stream_reader` and `file_splitter` open their path with `SplFileObject`, any PHP stream wrapper is accepted (`http://`, `php://filter`...); the path must not be built from untrusted input (SSRF, reading local files), validate it before the task.

## Fixes
* [#459](https://github.com/cleverage/process-bundle/issues/459) `process_launcher`: the sub-processes still running are stopped together (`SIGTERM` to all of them, `SIGKILL` after 5 seconds), instead of one after the other with up to 5 seconds each (40 seconds for 8 sub-processes, longer than the grace period of `docker stop` or Kubernetes). Update documentation, add tests.
* [#455](https://github.com/cleverage/process-bundle/issues/455) `cleverage:process:execute -o <file> -t json-stream`: the invalid UTF-8 sequences of the output are replaced, as on STDOUT (a `JsonException` was thrown, also by the buffer of `process_launcher`); an output that cannot be encoded as JSON is reported on the error output. Update documentation, add tests.
* [#457](https://github.com/cleverage/process-bundle/issues/457) `mapping` / `callback`: the deprecations of a literal key of an array destination (once per target property) and of `additional_parameters` are triggered once, instead of for each item or each resolution of the options. Add tests.
* [#453](https://github.com/cleverage/process-bundle/issues/453) `trim`: the ranges of single-byte characters (`a..z`) of a `charlist` with a multibyte character are expanded before `mb_trim()`, which does not support them (the range was lost, its `.` removed). Update documentation, add tests.
* [#451](https://github.com/cleverage/process-bundle/issues/451) `process_launcher`: when the process fails or is stopped, the JSON buffers of the terminated sub-processes not output yet are removed too, as documented (they were left in the temporary directory). Add tests.
* [#434](https://github.com/cleverage/process-bundle/issues/434) Signals: when the same SIGINT or SIGTERM is received again while a process is running, the command exits right away, as documented. The console application dispatches the same event at each signal, whose exit stayed aborted by the first signal. Add tests.
* [#436](https://github.com/cleverage/process-bundle/issues/436) `xpath_evaluator`: a boolean expression whose value is `false` (`boolean()`, `not()`, a false comparison) returns `false`, instead of throwing `Invalid xpath query '...': unknown error`. Only an expression reporting libxml errors is invalid. Add tests.
* [#438](https://github.com/cleverage/process-bundle/issues/438) Generic transformers: the codes of their `transformers` and `contextual_options` keep their dashes, as the codes of the processes, tasks and generic transformers since 5.7. A generic transformer referencing a generic transformer whose code has a dash no longer triggers the deprecation of its former name, and a transformer service whose code has a dash is found. Update documentation, add tests.
* [#447](https://github.com/cleverage/process-bundle/issues/447) `date_parser`: the trailing data allowed by a `+` in the format (e.g. `!Y-m-d+`) is no longer rejected (`Trailing data` warning, rejected since 5.7 with the other warnings). Update documentation, add tests.
* [#449](https://github.com/cleverage/process-bundle/issues/449) `sprintf`: a width or a precision given as an argument (`%*d`, `%.*f`, `%1$*2$d`) is accepted by the validation of the `format` option (rejected since 5.7). Add tests.

v5.8
-----

## Changes
* [#432](https://github.com/cleverage/process-bundle/issues/432) Documentation: the `%f` conversion of the `sprintf` transformer depends on the locale set with `setlocale()` (`1,50` with `fr_FR.UTF-8`), `%F` is recommended for a number written to a file or sent to an API.
* [#426](https://github.com/cleverage/process-bundle/issues/426) A task code referenced by the `outputs`, the `error_outputs` (or the deprecated `errors`) of a task or by the `entry_point` / `end_point` of a process, and that is not a task of this process, is deprecated when the container is compiled: the deprecation names the process, the task and the key holding the reference. It will be rejected when the container is compiled in v6.0; the process already fails when it is executed (#372). Update documentation, add tests.

## Fixes
* [#430](https://github.com/cleverage/process-bundle/issues/430) `mapping` / `property_accessor`: an input that is neither an array nor an object (`null`, a scalar) has no readable property. With `ignore_missing`, its properties are missing (skipped by `mapping`, `null` for `property_accessor`); otherwise a `NoSuchPropertyException` naming the property and the type of the input is thrown, instead of the `TypeError` of the PropertyAccessor (`must be of type object|array, null given`) thrown in both cases. Update documentation, add tests.

v5.7
-----

## Changes
* [#419](https://github.com/cleverage/process-bundle/issues/419) SIGTERM and SIGINT received by a console command during a process (`cleverage:process:execute`, `messenger:consume`...) stop the process before its next task (new internal `SignalStopChecker`, a stop checker listening to `ConsoleEvents::SIGNAL`), instead of exiting right away: the tasks are finalized and the process fails with a `ProcessStoppedException` (`cleverage_process.fail` dispatched), `cleverage:process:execute` exits with `1`. A second signal exits right away. Requires the `pcntl` extension (added to the Docker image of the tests). Update documentation, add tests.
* [#418](https://github.com/cleverage/process-bundle/issues/418) `csv_writer`: new `escape_formulas` option (`false` by default), as the `csv_escape_formulas` context of the Symfony Serializer `CsvEncoder`: the values and the headers starting with `=`, `-`, `+`, `@`, a tabulation, a carriage return or a line feed are prefixed with a single quote, so that a spreadsheet application does not interpret them as formulas (CSV injection). Update documentation, add tests.
* [#414](https://github.com/cleverage/process-bundle/issues/414) Listing a task several times in the `outputs` or the `error_outputs` of a task is deprecated: it is executed once per occurrence, an exception will be thrown in v6.0 (see `UPGRADE.md`). Update documentation, add tests.
* [#406](https://github.com/cleverage/process-bundle/issues/406) Documentation: non-interactive install with Symfony Flex (`allow-contrib`, the recipe being in `recipes-contrib`), `$kernel` constructor parameter renamed in 5.4 in `UPGRADE.md` (breaks the named arguments), prerequisites of the functional test of `06-testing.md` (`KERNEL_CLASS`, test database with the UI bundle).
* [#392](https://github.com/cleverage/process-bundle/issues/392) Documentation: reset of the process configurations between two messages of a worker (`kernel.reset`, #355), permissions of the JSON buffer of `process_launcher`, `ProcessManager` subclasses in `UPGRADE.md` (v5.5), broken link in the CHANGELOG.

## Fixes
* [#424](https://github.com/cleverage/process-bundle/issues/424) A flushable task (`simple_batch`, `iterable_batch`, `counter`...) used as `end_point` keeps its output: the process returned `null`, the output being reset by each flush of the task (it is flushed several times), the last one having nothing to output. A flush with nothing to output (skipped) keeps the last output of the task. Update documentation, add tests.
* [#416](https://github.com/cleverage/process-bundle/issues/416) Process graph: `TaskConfiguration::hasAncestor()` / `hasDescendant()` visit each task once, and the cycles are checked once to sort the dependencies: a chain of "diamonds" of 49 tasks took 22 s to resolve (each `cleverage:process:list`, each page of the UI), and a cycle not containing the searched task made the search recurse endlessly. A task reached from a root through outputs is no longer flagged as in the error branch when it is also reached through an error output (`cleverage:process:help`). Add tests.
* [#412](https://github.com/cleverage/process-bundle/issues/412) The codes of the processes, of their tasks and of the generic transformers keep their dashes (`normalizeKeys(false)`): the Symfony configuration replaced them by underscores, while the references to them kept the dash and failed (`Unknown process demo.x-y`, `No task configuration with code : next-task`, `No transformer with code : to-upper`). Their former name (with underscores) still works, deprecated since 5.7, until v6.0 (see `UPGRADE.md`). Update documentation, add tests.
* [#408](https://github.com/cleverage/process-bundle/issues/408) Regular expressions of `preg_match`, `preg_filter`, `slugify` (`replace`) and of the conditions (`match_regexp`, `not_match_regexp` of `filter`, `column_aggregator`, `array_filter`, `unset`): an invalid pattern is rejected when the options are resolved (`InvalidOptionsException`), instead of a PHP warning with the first value then "no match"; a PCRE error while matching (invalid UTF-8 with the `u` modifier, backtracking limit) throws an `UnexpectedValueException`, instead of being taken for "no match" (both `match_regexp` and `not_match_regexp` excluded the input). Update documentation, add tests.
* [#410](https://github.com/cleverage/process-bundle/issues/410) `xpath_evaluator`: the queries are evaluated with `\DOMXPath::evaluate()`, an expression that does not return nodes (`count(...)`, `string(...)`, a comparison) returns its value (`float`, `string`, `bool`) instead of `null`; new `namespaces` option (prefix => URI) to query the elements of a namespace, including the default namespace of a document (documented). Documentation: the `mb_*` functions in the examples of `callback`, `generic_transformers` and `array_map`. Add tests.
* [#402](https://github.com/cleverage/process-bundle/issues/402) `date_parser`: a date or a time out of range (`2024-02-31`, `25:61`), parsed by `\DateTime::createFromFormat()` with a warning and overflowing to the next month or day, throws an `UnexpectedValueException`, as documented for a value that cannot be parsed. A scalar (e.g. an `int` timestamp with the `U` format) or `\Stringable` input is cast to string, an array or another object throws an `UnexpectedValueException` instead of a `TypeError`. Update documentation, add tests.
* [#398](https://github.com/cleverage/process-bundle/issues/398) Transformers edge cases. `trim`: a `charlist` with multibyte characters is applied with `mb_trim()` (it produced invalid UTF-8). `convert_value`: with `keep_missing` and `auto_cast`, the input itself is returned, as documented, not its value cast to string. `multi_replace`: the numeric keys of `replace_mapping` no longer throw a `TypeError`, and a replacement that is not a scalar is rejected with the configuration. Conditions (`filter`, `column_aggregator`, `array_filter`, `unset`): the list form of `empty` / `not_empty` (`not_empty: ['[email]']`), described by the documentation, and the numeric property paths no longer throw a `TypeError`. `hash`: an array or a non-`Stringable` object throws an `UnexpectedValueException` (an array was hashed as the string `Array`). The empty `delimiter` of `explode`, an invalid `format` of `sprintf` and an unknown `class` of `instantiate` are rejected with the configuration, not with the first value. Update documentation, add tests.
* [#400](https://github.com/cleverage/process-bundle/issues/400) `slugify`: new `strip_tags` option (`true` by default) to keep a text containing a `<` that is not HTML (`strip_tags()` removed everything after it); the result is lowercased with `mb_strtolower()`; a scalar or `\Stringable` input is cast to string, `null`, an array or another object throws an `UnexpectedValueException` instead of a `TypeError`. Documentation: the default transliterator removes the letters that are not decomposed (`Cœur` becomes `c_ur`), `Any-Latin; Latin-ASCII` is recommended. Add tests.
* [#396](https://github.com/cleverage/process-bundle/issues/396) A failure of `finalize()` on a process that has succeeded makes the process fail with a `ProcessFailedException`, once every task is finalized: the process was flagged and logged as failed, but ended successfully (`cleverage_process.end` event, the command exiting with `0`, a parent process going on). `ProcessLauncherTask` is finalizable: the sub-processes still running when the process fails or is stopped are stopped (a warning is logged) and the JSON buffers not output yet are removed, instead of going on with their side effects after the end of the process. Update documentation, add tests.
* [#394](https://github.com/cleverage/process-bundle/issues/394) `ProcessManager::execute()`: an exception thrown by a listener of `cleverage_process.fail` (`ProcessEvent::EVENT_PROCESS_FAILED`) is logged as an error and no longer replaces the error of the process, which is rethrown to the command, the parent process or Messenger. The debug message "has error outputs but it's error strategy 'stop' implies they will never be reached" is removed: the error branch of a task with the `stop` strategy is executed before the process stops. Update documentation, add tests.
* [#388](https://github.com/cleverage/process-bundle/issues/388) The temporary files of `file_splitter`, `csv_splitter` and of the JSON buffer of `process_launcher` (#343) are created with the `0077` umask: they are no longer readable by other users before their permissions are set to `0600`. Add tests.
* [#390](https://github.com/cleverage/process-bundle/issues/390) `cleverage:process:execute` with `--output-format=json-stream` and STDOUT (#353): the invalid UTF-8 sequences of the output are replaced, and an output that still cannot be encoded as JSON (e.g. `NAN`) is reported on the error output instead of throwing a `JsonException` that made the command fail after the execution and skipped the next processes. `UPGRADE.md` (v5.5): the `dump` format without `symfony/var-dumper` and the unknown formats make the command fail before any execution. Update documentation, add tests.

v5.6
-----

## Changes
* [#371](https://github.com/cleverage/process-bundle/issues/371) `ProcessManager`: executing a process while it is already running, by itself or through its sub-processes (`A → B → A` with `ProcessExecutorTask` (`process_executor`)), is deprecated and will throw an exception in 6.0: the second execution resets the states and task instances of the running process (one configuration per process code), which then returns the output of the deepest execution. The deprecation names the chain of processes; the execution goes on as before. The same sub-process executed several times in a row, or a process executed again by a listener of the process events, is not concerned. Update documentation, add tests.
* [#382](https://github.com/cleverage/process-bundle/issues/382) A shared service referenced by several tasks of the same process is deprecated instead of being rejected when the container is compiled (5.5, #339): this configuration worked before 5.5, all these tasks running with the same instance. The deprecation names the service, the tasks and the process; it will be rejected in 6.0. Declare the task services with `shared: false`. Update documentation, update tests.

## Fixes
* [#367](https://github.com/cleverage/process-bundle/issues/367) `ProcessLauncherTask` (`process_launcher`) with `json_buffering`: when the sub-process writes nothing into its JSON buffer (no `end_point`, or an output that is not an array), the task outputs nothing again, as before 5.5, instead of the path of an empty file, and the empty buffer is removed. The buffer of a failed sub-process, and those of the still running sub-processes stopped because of it, are removed too (one file was left in the temporary directory for each launch). Update documentation, add tests.
* [#368](https://github.com/cleverage/process-bundle/issues/368) `YamlWriterTask` (`yaml_writer`): the parent directory is created for a local path only (no scheme, or `file://`), not for a stream wrapper (`compress.zlib://`, `s3://`...), which failed, or `php://stdout`, which created a `php:` directory. Update documentation, add tests.
* [#369](https://github.com/cleverage/process-bundle/issues/369) `StopTask` (`stop`): a process that has already failed (e.g. after a task initialization failure, going on being deprecated) stays failed instead of being flagged as stopped: the `Process <code> failed` log is written at the `failed_level` and `ProcessEvent::isProcessStopped()` is `false`, as before #337. Update documentation, add tests.
* [#370](https://github.com/cleverage/process-bundle/issues/370) `RulesTransformer` (`rules`): the result of a `condition` is cast to `bool`, so a condition returning an `int` (`preg_match()`), a string, `null` or any other non-boolean value (a single variable) no longer throws a `TypeError`: the rule matches if the result is truthy. Update documentation, add tests.
* [#372](https://github.com/cleverage/process-bundle/issues/372) `ProcessConfigurationRegistry`: an unknown task in the `outputs` or `error_outputs` of a task, or in the `entry_point` or `end_point` of a process, throws an `InvalidProcessConfigurationException` naming the process, the task and the key holding the reference, instead of a `MissingTaskConfigurationException` (`No task configuration with code : <code>`). A process whose configuration is not valid is left out of `getProcessConfigurations()` (`cleverage:process:list`, lists of the UI) with an error log on the `cleverage_process` channel, instead of making every listing fail; getting or executing this process still throws. Update documentation, add tests.
* [#373](https://github.com/cleverage/process-bundle/issues/373) `ProcessManager`: the tasks are finalized (`FinalizableTaskInterface::finalize()`) when the process fails too, so that a long-running worker (Messenger, UI bundle) no longer keeps the resources of a failed process open (files of the CSV, JSON stream and XML tasks, cursor of `doctrine_database_reader` blocking the next queries with MySQL unbuffered queries). Only the initialized tasks are finalized, each one once; a finalization failure is logged as critical and the process fails with its original exception. `StatCounterTask` (`stat_counter`) logs its count when the process fails too. Update documentation, add tests.

v5.5
-----

## Changes
* [#365](https://github.com/cleverage/process-bundle/issues/365) `ArrayElementTransformer` (`array_element`) and `ArrayLastTransformer` (`array_last`) return `null` when the position is outside the array (or the array is empty), without the "Undefined array key" PHP warning (it was an exception in debug mode, `null` in production); a negative `index` before the start of the array no longer returns its first element. `InputCsvReaderTask` (`input_csv_reader`): an input that is not a path (e.g. `null`) throws an explicit `UnexpectedValueException` instead of an "Undefined array key" PHP warning followed by a `TypeError`. Documentation: rate `n/a` of `advanced_stat_counter`, `headers: []` of `csv_reader` / `input_csv_reader`, optional `$accessor` of `ConditionTrait`, `ParsedExpression` accepted by the `condition` of `rules`. Add tests.
* [#337](https://github.com/cleverage/process-bundle/issues/337) `StopTask` (`stop`): a process stopped on purpose is identified as stopped, not as failed. Its history is flagged as stopped (new `ProcessHistory::STATE_STOPPED`, `setStopped()`, `isStopped()`) instead of failed, the `Process <code> stopped` log is written at the `success_level` (instead of `Process <code> failed` at the `failed_level`), and the `cleverage_process.end` event tells it with the new `ProcessEvent::isProcessStopped()`. The process still ends normally (tasks finalized, exit code 0, parent process going on). Update documentation, add tests.
* [#339](https://github.com/cleverage/process-bundle/issues/339) A shared service referenced by several tasks of the same process is rejected when the container is compiled (`InvalidArgumentException` naming the service, the tasks and the process): all these tasks ran with the same instance, initialized with the options of each task in turn, without error. Declare the task services with `shared: false` (applied by the autoconfiguration of `TaskInterface` since 5.4). Update documentation, add tests.
* [#351](https://github.com/cleverage/process-bundle/issues/351) `ProcessManager` and `ProcessHelpCommand` are typed on `TaskRegistryInterface` instead of the final `TaskRegistry` class: the task registry can be decorated or replaced. Add tests.
* [#357](https://github.com/cleverage/process-bundle/issues/357) Documentation: input of `file_splitter`, `log_empty_lines` of the CSV readers, empty lines of the line readers, invalid regular expressions of the conditions, cache pool of the `cached` transformer, missing serializer / validator, exceptions of the bundle in the reference pages, failure of a sub-process of `process_executor`, `sleep_on_finalize_interval` of `process_launcher`, initialization failures, `process_options` of `process_launcher` in `UPGRADE.md`.

## Fixes
* [#343](https://github.com/cleverage/process-bundle/issues/343) The temporary files of `FileSplitterTask` (`file_splitter`), `CsvSplitterTask` (`csv_splitter`) and of the JSON buffer of `ProcessLauncherTask` (`json_buffering`) are created with a random name, exclusively (an existing path, symbolic links included, is never reused) and readable by their owner only, instead of a name derived from the time (`uniqid()`), opened in `w` mode with the permissions of the umask. Update documentation, add tests.
* [#331](https://github.com/cleverage/process-bundle/issues/331) `ProcessManager`: the iterable tasks that produced items and the blocking tasks waiting for `proceed()` are tracked by task configuration instead of task code, and forgotten when their process ends. A task of a sub-process (`process_executor`) with the same code as a task of the parent no longer makes the parent lose the output of its blocking task (without error) or skip the flush at the end of its iteration (merged batches), and a failed sub-process no longer leaves its tasks to its next execution. Add tests.
* [#333](https://github.com/cleverage/process-bundle/issues/333) `ProcessManager`: restore the previous task configuration (`getTaskConfiguration()`, read by the log processors for `task_code` and `task_service`) when a task, an initialization, a finalization or a sub-process ends. The logs of an iterable task no longer lose their task after its first item, and the logs of a parent process are no longer attributed to a task of its sub-process; the failing task is kept for the logs of the process failure. Add tests.
* [#335](https://github.com/cleverage/process-bundle/issues/335) `LineReaderTask` (`line_reader`) and `InputLineReaderTask` (`input_line_reader`): the last line of a file that does not end with a line break is no longer lost. Add tests.
* [#345](https://github.com/cleverage/process-bundle/issues/345) `YamlWriterTask` (`yaml_writer`): a write failure throws a `RuntimeException` instead of a PHP warning (an exception in debug mode only: in production, the path was output as if the file had been written), and the parent directory is created if needed, as for the other writers. Update documentation, add tests.
* [#353](https://github.com/cleverage/process-bundle/issues/353) `cleverage:process:execute`: the process codes and the output format are checked before any process is executed (explicit message for `dump` without `symfony/var-dumper`); the output is printed to STDOUT whatever the verbosity when `--output-format` is given (it required `-vv`); a non-array output that cannot be stored as JSON stream is reported on the error output instead of being silently lost. Update documentation, add tests.
* [#355](https://github.com/cleverage/process-bundle/issues/355) `ProcessConfigurationRegistry::reset()`, tagged `kernel.reset`: a long-running worker (Messenger, UI bundle) releases the resolved process configurations, with the task instances and states (inputs, outputs, aggregated data) of their last execution, between two messages. Add tests.

v5.4.1
-----

## Changes
* [#329](https://github.com/cleverage/process-bundle/issues/329) Documentation: the contextual values may come from users who do not write the process configuration (console, parent process, UI) and are logged: keep them out of the options interpreted by the tasks (string `commandline` of `command_runner`, SQL...) and do not pass secrets in the context.
* [#90](https://github.com/cleverage/process-bundle/issues/90) Installation documentation: describe the Symfony Flex recipe ([symfony/recipes-contrib#2081](https://github.com/symfony/recipes-contrib/pull/2081)), which registers the bundle and creates `config/packages/clever_age_process.yaml` importing the `config/packages/process/` directory, and the manual setup without Flex.

## Fixes
* [#327](https://github.com/cleverage/process-bundle/issues/327) `ContextualOptionResolver`: quote the context keys in the pattern of the `{{ key }}` placeholders (a key containing `/` made every string option fail, a key containing a regex character matched other placeholders), and leave the options as is with an empty context (`{{ }}` matched an empty key).

v5.4
-----

## Changes
* [#320](https://github.com/cleverage/process-bundle/issues/320) README: add an installation section (`composer require`, bundle registration without Symfony Flex), linking to the quick start.
* [#319](https://github.com/cleverage/process-bundle/issues/319) Composer: require PHP `^8.2` instead of `>=8.2` (PHP 9 is no longer accepted before being tested), remove the authorization of the `symfony/flex` plugin, which is not required (the CI installs it globally).
* [#318](https://github.com/cleverage/process-bundle/issues/318) `CommandRunnerTask`, `ProcessLauncherTask` and `SubprocessInstance` receive the project directory, the path of the console and the environment (`%kernel.project_dir%`, `%kernel.environment%`) instead of the kernel: passing a `KernelInterface` is deprecated, the arguments will only accept strings in v6.0. `SubprocessInstance` writes the JSON buffer of `ProcessLauncherTask` (`json_buffering`) in the system temporary directory instead of `var/` of the project (which may be read-only), no longer creates the unused `<log dir>/process` directory, and omits `--env` when no environment is given (default environment of the console). Update documentation, add tests.
* [#317](https://github.com/cleverage/process-bundle/issues/317) The bundle throws exceptions implementing `ProcessExceptionInterface` instead of raw SPL exceptions (124 `throw`): new `Exception\UnexpectedValueException`, `RuntimeException`, `InvalidArgumentException` and `LogicException`, extending the SPL exceptions thrown before (same messages, the existing `catch` keep working). All the errors of the bundle can be caught with `ProcessExceptionInterface`, except the exceptions of the Symfony components. Update documentation, add tests.
* [#311](https://github.com/cleverage/process-bundle/issues/311) Deprecate the `cleverage_process.event_listener.data_queue` service and `DataQueueEventListener` (it keeps a copy of the `ProcessState` of every `cleverage_process.data_queue` event in memory, without limit): injecting or fetching the service triggers a deprecation, they will be removed in v6.0. Write your own listener of the event dispatched by the `event_dispatcher` task. Update documentation, add tests.
* [#312](https://github.com/cleverage/process-bundle/issues/312) Add read-only interfaces for the process manager and the registries, implemented by the existing classes and autowired by their interface: `ProcessManagerInterface` (`execute()`), `ProcessConfigurationRegistryInterface` (`getProcessConfiguration()`, `getProcessConfigurations()`, `hasProcessConfiguration()`), `TransformerRegistryInterface` (`getTransformer()`, `getTransformers()`, `hasTransformer()`) and `TaskRegistryInterface` (`getTask()`). Type your services on them: the classes will be `final` in v6.0 (the services of the bundle will then be typed on the interfaces). Update documentation, add tests.
* [#310](https://github.com/cleverage/process-bundle/issues/310) Process configuration tree: a task `error_strategy` other than `skip` or `stop` is deprecated (notice when the container is compiled; it was accepted and the process failed with "Unknown error strategy" when the task failed), it will be rejected in v6.0. The process `public` key is a boolean node: a non-boolean value is rejected when the container is compiled, instead of a `TypeError` when the configuration is built. Describe the nodes of the process, task and generic transformer configurations (`config:dump-reference clever_age_process`). Update documentation, add tests.
* [#309](https://github.com/cleverage/process-bundle/issues/309) Composer: `ext-intl` is no longer required, it is suggested: it is only needed by the `slugify` transformer (`SlugifyTransformer`), which is replaced by a service failing with an explicit message when the extension is missing (`UnavailableServicesCompilerPass`). Update documentation, add tests.
* [#305](https://github.com/cleverage/process-bundle/issues/305) Bundle best practices, residues: the generic transformers are no longer autowired (explicit arguments). `ProcessHelpCommand` receives the `TaskRegistry` instead of the service container (passing a container is deprecated). Fetching from the service container a task of the application made public by the `TaskInterface` autoconfiguration is deprecated (`container.private` tag, new internal `PublicTaskDeprecationCompilerPass`); the tasks made public by the application itself are not concerned. Trigger the missing deprecation notices: `CheckSerializerCompilerPass`, `errors` task configuration key (use `error_outputs`). `TaskConfiguration::getErrors()` triggers a single notice on PHP 8.4+. `CleverAgeProcessBundle` is marked `@final since 5.4`. Update documentation, add tests.
* [#299](https://github.com/cleverage/process-bundle/issues/299) Require `symfony/polyfill-php83`, `symfony/polyfill-php84` and `symfony/polyfill-php85`: the functions of PHP 8.3 to 8.5 can be used while supporting PHP 8.2. Rector targets PHP 8.2 (`withPhpVersion()`, `withPhpSets()` read from `composer.json`) and applies the rules using a newer function when its polyfill is required: `ProcessConfiguration` uses `array_any()` again; `array_first()` replaces `reset()` / `current()` in `ProcessConfiguration::getMainTask()`, `ConstraintLoader` and `ProcessHelpCommand` (no internal array pointer, same results).
* [#279](https://github.com/cleverage/process-bundle/issues/279) Define the public API (Symfony BC promise): the classes not meant to be extended are marked `@final since 5.4` (the engine: `ProcessManager`, `ProcessConfigurationRegistry`, `TransformerRegistry`, `ContextualOptionResolver`; the models: `ProcessState`, `ProcessHistory`, `SubprocessInstance`, the configurations, the events, the `Filesystem` files; the concrete tasks and transformers), they will be `final` in v6.0: extending them triggers a deprecation with the Symfony `DebugClassLoader`. The abstract classes, interfaces, traits, exceptions and the file tasks extended by other tasks (`FileReaderTask`, `LineReaderTask`, `FolderBrowserTask`, `CsvReaderTask`, `InputCsvReaderTask`, `Filesystem\CsvResource`) stay open. `TaskRegistry`, new in this version, is `final`. The infrastructure classes are marked `@internal`: `CleverAgeProcessExtension`, `Configuration`, the commands, `DataQueueEventListener`, `PhpFunctionProvider`, the Monolog processors, `ProcessLogger` and `TaskLogger`. Update documentation.
* [#269](https://github.com/cleverage/process-bundle/issues/269) Composer: `symfony/process`, `symfony/stopwatch`, `symfony/var-dumper` and `symfony/monolog-bundle` are no longer required, they are suggested (applications using them must require them). Without `symfony/process` (`CommandRunnerTask`, `ProcessLauncherTask`) or `symfony/stopwatch` (`StopwatchTask`), the container still compiles and these tasks fail with an explicit message (`UnavailableServicesCompilerPass`); without `symfony/var-dumper`, nothing is dumped (as before); without `symfony/monolog-bundle`, the `cleverage_process*` channels and processors are not registered and the default logger is used. `monolog/monolog` stays required (`Logger\*Processor`). Remove the unused `symfony/monolog-bridge` dependency. `ProcessConfiguration` no longer uses `array_any()` (PHP 8.4), whose polyfill was only installed by the removed packages. Add a `conflict` with `cleverage/doctrine-process-bundle`, `cleverage/soap-process-bundle` and `cleverage/ui-process-bundle` `<3.3`, which use the MonologBundle without requiring it. Update documentation, add tests.
* [#91](https://github.com/cleverage/process-bundle/issues/91) Reference a task by a code in the process configurations: new `task` key (`task: transformer`), next to its `options`. The code of a task service is declared with the new `code` attribute of the `cleverage.task` tag or the new `#[AsTask(code: '...')]` attribute (autoconfiguration); every task of the bundle has a code, given on its reference page (snake case of its class name without `Task`, e.g. `transformer`, `constant_output`, `csv_reader`). The codes are resolved when the container is compiled (`TaskLocatorCompilerPass`): an unknown or duplicate code fails the compilation. Referencing a task by its `service` (e.g. `service: '@CleverAge\ProcessBundle\Task\TransformerTask'`) is deprecated (configuration deprecation), it will be removed in v6.0: use `task: <code>`. The documentation examples use the codes. Update documentation, add tests.
* [#276](https://github.com/cleverage/process-bundle/issues/276) TransformerRegistry: the transformers are loaded lazily from a service locator indexed by code (new internal `TransformerLocatorCompilerPass`, new optional `TransformerRegistry` constructor argument): a transformer is only instantiated when a process uses it. The code is given by the new `code` attribute of the `cleverage.transformer` tag, or by the new `#[AsTransformer(code: '...')]` attribute (autoconfiguration); it must be the one returned by `getCode()`. All the bundle transformers and the generic transformers have their code. Duplicate codes fail the container compilation. A transformer tagged without `code` is still registered eagerly, with `TransformerRegistry::addTransformer()`, which is deprecated (the `code` will be required in v6.0, #280). `RegistryCompilerPass` is no longer used and deprecated. `TransformerInterface::getCode()` is deprecated (`@deprecated` PHPDoc: no runtime notice is possible for an interface method), it will be removed from the interface in v6.0 (#280): keep implementing it, returning the code of the tag until then. Update documentation, add tests.
* [#149](https://github.com/cleverage/process-bundle/issues/149) Apply Symfony best practices to the tasks and transformers services: they are defined explicitly (no more autowiring, autoconfiguration nor bindings in the bundle), keeping their class name as service id. Same arguments, tags and `shared` settings as before (generated from the previously autowired definitions). They stay public, but fetching them directly from the service container (`$container->get(<class>)`) is deprecated (`container.private` tag): they will be private in v6.0, use dependency injection (autowiring by class and decoration keep working). Update documentation.
* [#275](https://github.com/cleverage/process-bundle/issues/275) The tasks are fetched from a service locator built at compile time from the services referenced by the process configurations (new `TaskRegistry`, `cleverage_process.registry.task`, and internal `TaskLocatorCompilerPass`) instead of the whole service container: a task service can be private. `ProcessManager` and `ProcessHelpCommand` get the registry as a new optional last constructor argument; not passing it is deprecated (the tasks are then fetched from the container), and fetching a task service not referenced by the process configurations known at compile time falls back on the container with a deprecation. Both fallbacks will be removed in v6.0 (#114). Update documentation, add tests.
* [#278](https://github.com/cleverage/process-bundle/issues/278) The container no longer fails to compile when the Symfony serializer or validator is not enabled: the tasks and transformers depending on them (`NormalizerTask`, `DenormalizerTask`, `SerializerTask`, `DeserializerTask`, `normalize` and `denormalize` transformers, `ValidatorTask`) are replaced by services failing with an explicit message when they are used (new internal `UnavailableServicesCompilerPass`). `CheckSerializerCompilerPass` is no longer used and deprecated. Update documentation, add tests.
* [#272](https://github.com/cleverage/process-bundle/issues/272) DependencyInjection: autoconfiguration of the services implementing `TaskInterface` (`public`, not shared, `monolog.logger` tag on `cleverage_process_task`) and `TransformerInterface` (`cleverage.transformer` tag, `monolog.logger` tag on `cleverage_process_transformer`): the `_instanceof` rules or explicit tags are no longer needed with `autoconfigure: true`; a setting explicitly defined on a service wins. Add FQCN aliases to autowire `ProcessManager`, `ProcessConfigurationRegistry`, `TransformerRegistry` and `ContextualOptionResolver`. Update documentation, add tests.
* [#274](https://github.com/cleverage/process-bundle/issues/274) CI: test the lowest versions allowed by `composer.json` (`--prefer-lowest`, PHP 8.2). Fix the lower bounds it revealed: require `psr/log` `^2|^3` (`Logger\AbstractLogger` and the loggers of the tests use the typed `LoggerInterface` signatures, a fatal error with `psr/log` 1) and `monolog/monolog` `^3` (the `Logger\*Processor` classes use `Monolog\LogRecord`, Monolog 3 only: a `TypeError` on every log of the `cleverage_process*` channels with Monolog 2).
* [#273](https://github.com/cleverage/process-bundle/issues/273) Trigger the deprecations with `trigger_deprecation()` (`symfony/deprecation-contracts`, now required) instead of `@trigger_error(..., E_USER_DEPRECATED)`: the messages are prefixed with the package and the version that introduced the deprecation (e.g. `Since cleverage/process-bundle 5.1: ...`), as for the Symfony packages. Same `E_USER_DEPRECATED` level. Add tests.
* [#270](https://github.com/cleverage/process-bundle/issues/270) Add a `.gitattributes`: the development files (`.docker/`, `.github/`, `tests/`, quality tools and PHPUnit configurations, `Makefile`…) are no longer part of the archive installed by Composer (`dist`). Remove the obsolete test fixtures of `src/Resources/tests/` (not used since the tests rewrite).
* [#271](https://github.com/cleverage/process-bundle/issues/271) DependencyInjection: `CleverAgeProcessExtension::findServices()` loads the service definition files with a glob pattern instead of a `Finder` (same files), `Configuration` uses `arrayPrototype()` / `variablePrototype()` / `scalarPrototype()` instead of `prototype()` (same configuration tree). Add tests.
* [#282](https://github.com/cleverage/process-bundle/issues/282) Require `psr/container` (`ProcessHelpCommand`), `ext-xmlreader` (`XmlStreamReaderTask`) and `ext-libxml` (`XmlFile`, `XmlStreamReaderTask`, `XpathEvaluatorTransformer`), used directly; add `symfony/cache` (used by the tests) to the dev requirements.
* [#268](https://github.com/cleverage/process-bundle/issues/268) Clean up composer.json: remove the unused `symfony/dotenv`, `symfony/runtime` and `symfony/error-handler` dependencies (applications relying on the bundle to install them must require them), bound `symfony/service-contracts` to `^2.5|^3`, update the package description.

## Fixes
* [#307](https://github.com/cleverage/process-bundle/issues/307) Fix InputAggregatorTask: when a branch skips an item (filter, error with the `skip` strategy), the inputs received from the other branches for this item are dropped instead of being joined with the inputs of the next item. The inputs of an item are told apart by the executions of their common ancestors (new `ProcessState::getExecutionCount()`, incremented by the `ProcessManager` before each execution of a task). InputAggregatorTask is no longer deprecated (its limits described in 2018 no longer exist) and is marked `@final since 5.4`. Update documentation, add tests.
* [#301](https://github.com/cleverage/process-bundle/issues/301) Fix ProcessManager: the history of the parent process is restored after a failed process too, so that the logs of a parent process going on after a failed sub-process (e.g. `process_executor` task with the `skip` error strategy) are no longer attributed to the sub-process (`process_id`, `process_code` and `process_context` added by the Monolog processors), and that no history of a failed process is left after its execution (`getProcessHistory()`). The `Critical process failure` log and the `cleverage_process.fail` event are still attributed to the failed process. `ProcessManager` implements `ResetInterface` (`kernel.reset` tag): its state is reset between two messages of a long-running worker. Add tests.

v5.3
-----

## Changes
* [#266](https://github.com/cleverage/process-bundle/issues/266) Allow to stop a running process from the outside: services implementing the new `ProcessStopCheckerInterface` (autoconfigured, tag `cleverage_process.stop_checker`, new optional `$stopCheckers` argument of `ProcessManager`) are asked before each task execution; when one of them returns `true`, the task is not executed and the process fails with a `ProcessFailedException` whose previous exception is the new `ProcessStoppedException`. Add documentation and tests.

v5.2
-----

## Changes
* [#197](https://github.com/cleverage/process-bundle/issues/197) Add missing tests: every Task and Transformer is now covered by unit tests. Remove the obsolete `tests.old` directory.
* [#143](https://github.com/cleverage/process-bundle/issues/143) Improve PHPStan configuration: remove all `ignoreErrors` and `@phpstan-ignore` comments (report unmatched ignored errors again), add missing iterable value types and generic types in PHPDoc, remove unreachable code in AbstractIterableOutputTask and InputAggregatorTask.
* [#143](https://github.com/cleverage/process-bundle/issues/143) Improve PHPStan level from 6 to 7, and fix the level 8 errors that do not require a signature change (the remaining ones, due to nullable return types such as `AbstractConfigurableTask::getOptions(): ?array`, will be fixed in v6.0). Default to a `ContextualOptionResolver` in ProcessState and to a property accessor in ConditionTrait when none is set.
* [#94](https://github.com/cleverage/process-bundle/issues/94) TransformerTrait: the `transformers` option (TransformerTask, MappingTransformer, ArrayMapTransformer, CachedTransformer, RulesTransformer, generic transformers) also accepts a list, whose items are a transformer code without options (`- trim`) or a single `code: options` map (`- callback: {...}`), to chain the same transformer without `#` suffix. The map syntax is still supported. Update documentation, add tests.
* [#117](https://github.com/cleverage/process-bundle/issues/117) MappingTransformer: writing a target property that is a nested path (e.g. `address.city`) on an array destination, which silently created an `address.city` literal key, is deprecated and will throw an `\UnexpectedValueException` in v6.0. Use `[address.city]` for a literal key or `[address][city]` for a nested array; a simple property name (e.g. `name`) is still added as a key. Update documentation, add tests.
* [#258](https://github.com/cleverage/process-bundle/issues/258) Add XmlStreamReaderTask: iterable task reading an XML file element by element with `\XMLReader` (constant memory), outputting each element named `node_name` as a `\DOMElement` in its own `\DOMDocument`, ready for XpathEvaluatorTransformer. The file path is set in the `file_path` option or given as input. Add documentation and tests.
* [#259](https://github.com/cleverage/process-bundle/issues/259) JsonStreamWriterTask: add a `mode` option (default `wb`, e.g. `ab` to append to an existing JSON Lines file), and release the file on `finalize()`. Update documentation, add tests.
* [#157](https://github.com/cleverage/process-bundle/issues/157) Configure the level of the logs written at the end of each process with the new `logs` option, globally (`clever_age_process.logs`) or per process (`configurations.<code>.logs`): `success_level` (default `info`) for `Process <code> succeed`, and `failed_level` (default `debug`) for the new `Process <code> failed` log, written when a process fails, with or without exception. Update documentation, add tests.

## Fixes
* [#143](https://github.com/cleverage/process-bundle/issues/143) Fix InputIteratorTask: an `\IteratorAggregate` input whose `getIterator()` does not return an `\Iterator` (e.g. another `\IteratorAggregate`) is iterated instead of failing with a `TypeError`. Update documentation, add tests.
* [#143](https://github.com/cleverage/process-bundle/issues/143) Fix level 8 edge cases failing with a `TypeError` or an `Error`: `CsvResource` rejects a blank header line and checks `ftell()`, XpathEvaluatorTransformer throws an explicit `\UnexpectedValueException` on an invalid query (with the libxml messages), a namespace node to unwrap or a node without document, SlugifyTransformer on a value that cannot be transliterated, `ProcessState::getContext()` returns `[]` until the context is defined, GenericTransformer and TransformerTrait throw a `\LogicException` when not initialized, IterableBatchTask and JsonStreamReaderTask can be flushed / iterated before their first execution, RegistryCompilerPass checks its configuration, SubprocessInstance checks the PHP executable. Update documentation, add tests.
* [#239](https://github.com/cleverage/process-bundle/issues/239) Fix PropertyGetterTask: catch any `\Throwable`, so that the `TypeError` of the property accessor on a scalar input is set on the state (with the `property` error context) and handled according to the task `error_strategy` instead of escaping it. Update documentation, add tests.
* [#241](https://github.com/cleverage/process-bundle/issues/241) Fix CsvWriterTask: a non-array input throws the documented `\UnexpectedValueException` instead of a `TypeError` when `headers` is `null`, and the CSV tasks release their file on `finalize()` so that the task can be executed again. Update documentation, add tests.
* [#240](https://github.com/cleverage/process-bundle/issues/240) Fix FileMoverTask: with `autoincrement`, the suffix was computed on the whole path (a dot in a directory name was taken as the extension, making the move fail for a file without extension) and an existing numeric suffix of the file name was replaced (`report-2024.csv` became `report-1.csv`). The suffix is now appended to the file name only (`report-2024-1.csv`, `file-1`, `.env-1`). Update documentation, add tests.
* [#243](https://github.com/cleverage/process-bundle/issues/243) Fix RecursivePropertySetterTransformer: a `\stdClass` item without the property was replaced by a copy in the output, so the input object was not modified; the property is now added to the item itself. Update documentation, add tests.
* [#242](https://github.com/cleverage/process-bundle/issues/242) Fix InputFileReaderTask: an input that is not a non-empty string (e.g. `null`) throws an explicit `\UnexpectedValueException` (`No file path given as input`) instead of a PHP warning followed by a `TypeError`. Update documentation, add tests.
* [#244](https://github.com/cleverage/process-bundle/issues/244) Fix MappingTransformer: a missing target property of a `\stdClass` destination (`initial_value` or `keep_input`) threw `Property '...' is not writable`, it is now added when the target is a simple property name (nested paths still throw). Update documentation, add tests.
* [#143](https://github.com/cleverage/process-bundle/issues/143) Type the remaining untyped task options: `split_character` of CsvWriterTask and SplitJoinLineTask must be a `string` (a wrong type used to fail later with a `TypeError`), `write_headers` of CsvWriterTask and `log_empty_lines` of CsvReaderTask are cast to `bool` (any value used to be evaluated as a boolean, so it is still accepted). Update documentation, add tests.

v5.1
-----

## Changes
* [#190](https://github.com/cleverage/process-bundle/issues/190) Update quality stack: use Rector `withComposerBased()` sets (removed `SYMFONY_64` / `PHPUNIT_100` sets), declare used Symfony packages and PHPUnit range in composer.json, apply quality tools fixes
* [#145](https://github.com/cleverage/process-bundle/issues/145) Add missing documentations: reference pages for every Task & Transformer, ConditionTrait & GenericTransformer, complete guides and cookbooks. Harmonize and fix existing documentation.
* [#229](https://github.com/cleverage/process-bundle/issues/229) StatCounterTask and AdvancedStatCounterTask pass their input to their output on every execution (they used to output `null`, and AdvancedStatCounterTask was skipped when not logging), so they can be placed anywhere in a branch. Update documentation, add tests.
* [#236](https://github.com/cleverage/process-bundle/issues/236) TransformerTrait: document that any non-empty `#suffix` is accepted on transformer codes (`callback#1`, `callback#reverse`), and anchor the matching regex (an empty suffix is no longer accepted). Update documentation, add tests.

## Fixes
* [#192](https://github.com/cleverage/process-bundle/issues/192) Fix CommandRunnerTask: only pass the `options` option to `Process::setOptions()`, support string `commandline` through `Process::fromShellCommandline()`, validate option types. Update documentation, add tests.
* [#194](https://github.com/cleverage/process-bundle/issues/194) Fix ProcessLauncherTask: the `process_options` normalizer returned an array despite its scalar return type, so the task always failed with a `TypeError`. Update documentation, add tests.
* [#189](https://github.com/cleverage/process-bundle/issues/189) Fix EventDispatcherTask: dispatch the event under the `event_name` option (regression since v4.0). `event_name` is now optional: when `null` (default), the event is dispatched under its class name. Update documentation, add tests.
* [#198](https://github.com/cleverage/process-bundle/issues/198) Fix DateParserTransformer: a `\DateTimeImmutable` input is converted to a `\DateTime` instead of throwing a `TypeError`. Update documentation, add tests.
* [#199](https://github.com/cleverage/process-bundle/issues/199) Fix PregFilterTransformer: an array `replacement` is no longer cast to the string `"Array"`, and requires an array `pattern`. Update documentation, add tests.
* [#200](https://github.com/cleverage/process-bundle/issues/200) Fix GenericTransformer: a contextual option declared with `required: false` and no default can now be used; its placeholders are replaced by `null` when omitted. Update documentation, add tests.
* [#202](https://github.com/cleverage/process-bundle/issues/202) Fix XmlReaderTask: throw an explicit `\UnexpectedValueException` on an empty file or invalid XML (with the libxml error messages) instead of a `ValueError` or a silent empty `\DOMDocument`. Update documentation, add tests.
* [#203](https://github.com/cleverage/process-bundle/issues/203) Fix `ProcessState::addErrorContextValue()`: accept any value type, so that iterable tasks on an empty iterable are skipped and PropertySetterTask reports the original exception instead of a `TypeError`. Update documentation, add tests.
* [#204](https://github.com/cleverage/process-bundle/issues/204) Fix CachedTransformer, SlugifyTransformer and TypeSetterTransformer edge cases: accept a non-string input in `cached` (key built after `key_transformers`), reject an invalid `transliterator` in `slugify` with an `InvalidOptionsException`, remove the unreachable error branch of `type_setter`. Update documentation, add tests.
* [#205](https://github.com/cleverage/process-bundle/issues/205) Fix FileSplitterTask: produced files lost the first line, doubled line breaks and ended with an empty file; each file now contains exactly `max_lines` lines of the source. Update documentation, add tests.
* [#206](https://github.com/cleverage/process-bundle/issues/206) Fix AdvancedStatCounterTask, IterableBatchTask, ConditionTrait and ColumnAggregatorTask edge cases: log the first counted execution with correct counts, accept a `null` `batch_count` (only flush at the end), allow scalar inputs in conditions (`''` path on the whole value), aggregate `null` column values. Update documentation, add tests.
* [#207](https://github.com/cleverage/process-bundle/issues/207) Fix JsonStreamReaderTask / JsonStreamWriterTask: throw an explicit `\UnexpectedValueException` when a line decodes to a scalar, create the missing parent directory when writing. Update documentation, add tests.
* [#208](https://github.com/cleverage/process-bundle/issues/208) Fix FolderBrowserTask, InputFolderBrowserTask, CsvReaderTask and LineReaderTask (and their `Input*` variants): reset the state at the end of the iteration, so that a following input is read from its beginning. Update documentation, add tests.
* [#201](https://github.com/cleverage/process-bundle/issues/201) Fix minor defects: error messages of TransformerTrait, RulesTransformer and ExpressionLanguageMapTransformer, useless `setRequired()` in ImplodeTransformer and SprintfTransformer, stray namespace in TrimTransformer, wrong or missing docblocks. Add tests.
* [#220](https://github.com/cleverage/process-bundle/issues/220) Fix the stop error strategy: throw a `ProcessFailedException` (with the original exception as `previous`) instead of a `FatalError`, so that the command exits with a non-zero code when a process fails and `ProcessLauncherTask` detects failed sub-processes. Update documentation, add tests.
* [#221](https://github.com/cleverage/process-bundle/issues/221) Fix CsvSplitterTask: each produced file contains `max_lines` data lines (instead of `max_lines - 2`), no infinite loop with `max_lines` <= 2 (`max_lines` must now be an integer greater than 0), no header-only file at the end. Update documentation, add tests.
* [#223](https://github.com/cleverage/process-bundle/issues/223) Fix CounterTask: the final count is outputted once, as `flush()` may be called several times. Document that `flush()` implementations must be idempotent. Update documentation, add tests.
* [#228](https://github.com/cleverage/process-bundle/issues/228) Add the missing `setAllowedTypes()` on HashTransformer (`raw_output`), SimpleBatchTask (`batch_count`), ConditionTrait (`empty`, `not_empty`), NormalizerTask / SerializerTask / DeserializerTask (`context`) and ObjectUpdaterTask (`property_path`): a wrong type is now reported when the options are resolved. Add tests.
* [#232](https://github.com/cleverage/process-bundle/issues/232) Fix edge cases looping forever or failing with a `TypeError`: validate FileSplitterTask `max_lines` (greater than 0, also when given as input), catch any `\Throwable` in PropertySetterTask, type SlugifyTransformer options, explicit exceptions in InputFolderBrowserTask (no folder path) and `XmlFile::write()` (`saveXML()` failure). Update documentation, add tests.

## Deprecated
* [#189](https://github.com/cleverage/process-bundle/issues/189) EventDispatcherTask: when `event_name` is set, listening to `CleverAge\ProcessBundle\Event\EventDispatcherTaskEvent` is deprecated (the event is still dispatched under its class name, with an `E_USER_DEPRECATED` error, if it has listeners). Listen to the configured `event_name` instead: the BC layer will be removed in v6.0.
* [#222](https://github.com/cleverage/process-bundle/issues/222) ProcessManager: going on with the process after an exception thrown by a task `initialize()` is deprecated (an `E_USER_DEPRECATED` error is triggered). In v6.0, the process will fail before executing any task.

v5.0
-----

## Changes
* [#179](https://github.com/cleverage/process-bundle/issues/179) Add support for PHP 8.5 and Symfony 8.* Update phpunit/phpunit to version >10.0

## BC breaks
* [#179](https://github.com/cleverage/process-bundle/issues/179) Remove support for PHP 8.1 and Symfony 7.3

v4.5
-----

## Changes
* [#176](https://github.com/cleverage/process-bundle/issues/176) Upgrade to Symfony 7.3 & PHP 8.4

v4.4
-----

### Add 
* [#168](https://github.com/cleverage/process-bundle/issues/168) Add JsonStreamWriterTask with related doc
* [#172](https://github.com/cleverage/process-bundle/issues/172) Add spl_file_object_flags and json_flags options on JsonStream*Tasks. Update docs.
* [#174](https://github.com/cleverage/process-bundle/issues/174) Add FileSplitterTask using Filesystem/SplFile. Add doc.

### Fixes
* [#169](https://github.com/cleverage/process-bundle/issues/169) Fix JsonStreamFile empty line at the end issue, even if SKIP_EMPTY is set. Add JsonStreamReaderTask doc.

v4.3
-----

### Add 
* [#166](https://github.com/cleverage/process-bundle/issues/166) Add PregMatchTransformer

v4.2
-----

### Add
* [#cleverage/archive-process-bundle#1](https://github.com/cleverage/archive-process-bundle/issues/1) Add new bridge ArchiveProcessBundle
* [#162](https://github.com/cleverage/process-bundle/issues/162) Add timestamp placeholder on file_path parameter of CsvWriterTask. Improve documentation.
* [#164](https://github.com/cleverage/process-bundle/issues/164) Add cleverage/cache-process-bundle dependency

v4.1.1
-----

### Fixes

* [#158](https://github.com/cleverage/process-bundle/issues/158) Add dependency to symfony/service-contract

v4.1
-----

### Add
* [#155](https://github.com/cleverage/process-bundle/issues/155) Add InputFileReaderTask (Reads the whole input file and outputs its content), InputLineReaderTask (Reads an input file line by line and outputs each line.) and LineReaderTask (Reads a file line by line and outputs each line.)

v4.0
------

## BC breaks

* [#142](https://github.com/cleverage/process-bundle/issues/142) Remove FileFetchTask, use `cleverage/flysystem-process-bundle` instead.
* [#142](https://github.com/cleverage/process-bundle/issues/142) YamlReaderTask & YamlWriterTask namespaces changed to `CleverAge\ProcessBundle\Task\File\Yaml`
* [#142](https://github.com/cleverage/process-bundle/issues/142) Array***Transformers namespaces changed to `CleverAge\ProcessBundle\Transformer\Array`
* [#142](https://github.com/cleverage/process-bundle/issues/142) NormalizeTransformer & DenormalizeTransformer namespaces changed to `CleverAge\ProcessBundle\Transformer\Serialization`
* [#142](https://github.com/cleverage/process-bundle/issues/142) DateFormatTransformer & DateParserTransformer namespaces changed to `CleverAge\ProcessBundle\Transformer\Date`
* [#142](https://github.com/cleverage/process-bundle/issues/142) ExplodeTransformer, HashTransformer, ImplodeTransformer, SlugifyTransformer, SprintfTransformer & TrimTransformer namespaces changed to `CleverAge\ProcessBundle\Transformer\String`
* [#142](https://github.com/cleverage/process-bundle/issues/142) InstantiateTransformer, PropertyAccessorTransformer RecursivePropertySetterTransformer namespaces changed to `CleverAge\ProcessBundle\Transformer\Object`
* [#147](https://github.com/cleverage/process-bundle/issues/147) Replace `Symfony\Component\Form\Exception\InvalidConfigurationException` by `Symfony\Component\Config\Definition\Exception\InvalidConfigurationException`
* [#148](https://github.com/cleverage/process-bundle/issues/148) Update services (step 1) according to Symfony best practices. Services should not use autowiring or autoconfiguration. Instead, all services should be defined explicitly. 
Services must be prefixed with the bundle alias instead of using fully qualified class names => `cleverage_process`
* [#150](https://github.com/cleverage/process-bundle/issues/150) The class `\CleverAge\ProcessBundle\Task\Debug\MemInfoDumpTask` has been deleted without suggested replacement
* [#115](https://github.com/cleverage/process-bundle/issues/115) New mandatory configuration `default_error_strategy` on `clever_age_process` level. See [Quick Start/Global configuration](docs/01-quick_start.md#global-configuration)
### Changes

* [#139](https://github.com/cleverage/process-bundle/issues/139) Update Makefile & .docker for local standalone usage
* [#139](https://github.com/cleverage/process-bundle/issues/139) Update rector, phpstan & php-cs-fixer configurations & apply it
* [#141](https://github.com/cleverage/process-bundle/issues/141) `league/flysystem-bundle` is not required anymore
* [#130](https://github.com/cleverage/process-bundle/issues/130) EventDispatcherInterface service declaration breaks dependency injection
* [#147](https://github.com/cleverage/process-bundle/issues/147) Add missing dependencies on `symfony/dotenv` and `symfony/runtime`
* [#147](https://github.com/cleverage/process-bundle/issues/147) Remove dependencies on `symfony/form`, `symfony/messenger` & `symfony/scheduler`
* [#146](https://github.com/cleverage/process-bundle/issues/146) eav-process-bundle, enqueue-process-bundle, cache-process-bundle and process-soap-bundle were deprecated / archived.
* [#141](https://github.com/cleverage/process-bundle/issues/141) Add a default value to the node "default_error_strategy"

### Fixes

* [#129](https://github.com/cleverage/process-bundle/issues/129) Remove wrong replace configuration on composer.json. Add missing suggest
* Miscellaneous fixes, show full diff : https://github.com/cleverage/process-bundle/compare/v4.0.0-rc2...v4.0.0

v4.0-RC2
------

## BC breaks

* Bump php version to >=8.2
* Bump symfony version to ^6.4|^7.1

### Fixes

* Miscellaneous fixes, show full diff : https://github.com/cleverage/process-bundle/compare/v4.0.0-rc1...v4.0.0-rc2

v4.0-RC1
------

## BC breaks

* Bump php version to >=8.1
* Bump symfony version to ^6.3

## Changes
* Add some phpunit tests
* Apply Rector & Phpstan
* Add StopwatchTask
* Change directory structure. Move Symfony code to /src, documentation to /doc, and tests to /tests

### Fixes

* Miscellaneous fixes, show full diff : https://github.com/cleverage/process-bundle/compare/v3.2.9...v4.0.0-rc1

v3.2.9
------

### Fixes

https://github.com/cleverage/process-bundle/compare/v3.2.8...v3.2.9

v3.2.8
------

### Fixes

https://github.com/cleverage/process-bundle/compare/v3.2.7...v3.2.8

v3.2.7
------

### Fixes

Suppress deprecation message

v3.2.6
------

### Fixes

Fix SubprocessInstance Task for Symfony >=5

v3.2.5
------

### Fixes

Upgrade psr/cache

v3.2.4
------

### Features

* Added a `ttl` option in the `cached` transformer

v3.2.3
------

### Features

* Added `multi_replace` transformer
* Added `cached` transformer

### Fixes

* Fixed return value of list and help commands (mandatory for Symfony 5)

### BC breaks

* Added `psr/cache` as a dependency, but it shouldn't break anything
* Added `ext-intl` as a dependency, since required by the `slugify` transformer

v3.2.2
------

### Fixes

* Ignore empty lines in `\CleverAge\ProcessBundle\Filesystem\CsvResource::getLineCount`.
* Fixed `\CleverAge\ProcessBundle\Task\AbstractIterableOutputTask` skipping iterations when inside another iteration loop
* `\CleverAge\ProcessBundle\Exception\InvalidProcessConfigurationException` now displays the failing process code
* `\CleverAge\ProcessBundle\Transformer\TransformerTrait` now displays a more explicit message on wrong options type


v3.2.1
------

### Fixes

* Fatal error while loading configuration in Symfony 3.4

v3.2.0
------

### Features

* [GITHUB-121](https://github.com/cleverage/process-bundle/issues/121): Enable compatibility with Symfony 5
* [GITHUB-118](https://github.com/cleverage/process-bundle/pull/118): Added boilerplate code to avoid deprecations notices for event listeners

### BC breaks

There is no BC break for this version, but note that `sidus/base-bundle` has been removed from dependencies.
If you use it, it should already be inside your own composer.json.



Release v3.1
============

v3.1-dev
------

### Features

_Nothing yet_

### Fixes

_Nothing yet_

### BC breaks

_Nothing yet_

v3.1.5
------

### Fixes

* [GITHUB-120](https://github.com/cleverage/process-bundle/pull/120): FolderBrowserTask: Accept array type for `name_pattern` option


v3.1.4
------

### Features

* (_backport from v3.0.9_) Adding simple task to launch system commands


v3.1.3
------

### Features

* (_backport from v3.0.7_) Allowing ValidatorTask to output constraint violations with an option
* (_backport from v3.0.6_) Adding ArrayUnsetTransformer
* (_backport from v3.0.5_) Adding basic debug transformer

### Fixes

* (_backport from v3.0.8_) Fixing AbstractIterableOutputTask that was inconsistent when chained, refactoring InputIteratorTask that had the proper implementation with the AbstractIterableOutputTask as parent

v3.1.2
------

### Fixes

* Fixed bad static access in tests

v3.1.1
------

### Features

* (_backport from v3.0.4_) Adding simple file reader task and cast transformer
* (_backport from v3.0.3_) FilterTask now outputs skipped content to error output

### Fixes

* Removed useless, CPU intensive, log on CsvSplitterTask

v3.1.0
------

### Features

* [GITHUB-83](https://github.com/cleverage/process-bundle/issues/83): added [events](docs/04-advanced_workflow.md#events)
  around process execution
* [GITHUB-86](https://github.com/cleverage/process-bundle/issues/86): added XML manipulation tools
* [GITHUB-109](https://github.com/cleverage/process-bundle/issues/109): added an event during CLI process execution
* [GITHUB-107](https://github.com/cleverage/process-bundle/issues/107): allow to use directly a string in task `outputs`
  and `errors` configurations

### Fixes

* [GITHUB-99](https://github.com/cleverage/process-bundle/issues/99): transformer exception message improvements

### BC breaks

* [GIHTUB-82](https://github.com/cleverage/process-bundle/issues/82): the `default_error_strategy` is now mandatory.
  If you have any doubt, you can use `default_error_strategy: skip` to keep previous behavior.
* [GITHUB-106](https://github.com/cleverage/process-bundle/issues/106): an entry-point cannot have an ancestor anymore.
  The behaviour was undefined, and now it will throw an exception.


UPGRADE TO 2.0
==============

Task Logging
------------

Instead of using `CleverAge\ProcessBundle\Model\ProcessState::log` you must now use the standard
`Psr\Log\LoggerInterface` with the `cleverage_process_task` chanel. You should also pass
`CleverAge\ProcessBundle\Model\ProcessState::getLogContext` to the log context.

TransformerTask
---------------

The main option is now "transformers", which accept transformer codes and then transformer options.
Default options should now look like:
```yaml
options:
    transformers:
        mapping:
            mapping:
                <key>: <options>
```


UPGRADE TO 1.1
==============

MappingTransformer
------------------

* The option "ignore_extra" is renamed to "keep_input".

Other
-----

* Fixed issues with blocking tasks
* Removed deprecated methods
* added input/output in process manager (may allow a start_process_task)

New issues :
* Error workflow

Planned (v2+)
============

* automated transformer creation & refactoring
    * easy test cases via yml ?
* changes in interfaces
    * allow blocking + iterable
* FIFO queues for in/out
