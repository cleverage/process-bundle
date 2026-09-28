---
name: Bug report
about: Report a task, transformer or engine behaving incorrectly
title: ''
labels: bug
assignees: ''
---

## Description

<!-- What is wrong, in one or two sentences. List each symptom when there are several (wrong output, exception, silent failure...) -->

### Reproduction

<!-- A minimal process configuration reproducing the bug, ideally a demo.* process from https://github.com/cleverage/process-bundle-demo -->

```yaml
clever_age_process:
    configurations:
        demo.<name>:
            tasks:
                <task>:
                    service: '@CleverAge\ProcessBundle\Task\...'
                    options: {}
                    outputs: [debug]
                debug:
                    service: '@CleverAge\ProcessBundle\Task\Debug\DebugTask'
```

<!-- Input data, command and actual output -->

```
$ bin/console cleverage:process:execute demo.<name>
```

Expected: `<expected output>`

Tested on `main` (`<commit>`), PHP x.y, Symfony x.y.

### Cause

<!-- Optional: where the bug comes from, with links to the relevant lines -->

### Proposed fix

<!-- Optional: how to fix it, and which documentation must be updated -->

## Requirements

* Documentation updates
  - [ ] Reference
  - [ ] Cookbooks
  - [ ] Changelog
* [ ] Unit tests

## Breaking changes

<!-- Behaviour changes introduced by the fix, or "None" with the reason (e.g. every configuration of this task currently fails) -->
