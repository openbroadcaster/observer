# Git Hooks

## pre-commit

`ln -s ../../dev/pre-commit .git/hooks/pre-commit`

This will run auto-format js/html/css (prettier) and php (phpcbf) before commit. If there is a problem that can't be fixed, the pre-commit will fail.