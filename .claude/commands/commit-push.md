# Commit and Push Changes

Commit all staged changes with a descriptive message and push to the current branch.

## Steps

1. Check git status to see what files have changed:
   ```bash
   git status
   ```

2. Review recent commits to match commit message style:
   ```bash
   git log --oneline -5
   ```

3. Stage all changes:
   ```bash
   git add -A
   ```

4. Create a commit with a clear message that:
   - Starts with a verb (Add, Fix, Update, Refactor, etc.)
   - Summarizes the "what" in the first line (max 72 chars)
   - Explains the "why" in the body if needed

5. Push to the current branch:
   ```bash
   git push
   ```

6. If push fails, retry up to 3 times with 2 second delays.

## Commit Message Format
```
<type>: <short summary>

<optional body explaining why>

<optional bullet points of specific changes>
```

Types: Add, Fix, Update, Refactor, Remove, Rename, Move
