# Verify All Changes

Run full verification workflow before committing changes.

## Steps

1. **TypeScript Check**
   ```bash
   cd france-relocation-member-tools/portal && npx tsc --noEmit 2>&1
   ```
   - Must pass with no errors
   - Fix any type errors before proceeding

2. **Lint Check**
   ```bash
   npm run lint 2>&1
   ```
   - Fix any lint errors
   - Warnings are acceptable but should be minimized

3. **Production Build**
   ```bash
   npm run build 2>&1
   ```
   - Must complete successfully
   - Note any warnings about chunk sizes

4. **Git Status**
   ```bash
   cd /home/user/france-relocation-assistant && git status
   ```
   - Review all changed files
   - Ensure no unintended changes

5. **Summary Report**
   Provide a summary:
   - [ ] TypeScript: PASS/FAIL
   - [ ] Lint: PASS/FAIL
   - [ ] Build: PASS/FAIL
   - [ ] Files changed: (count)
   - Any issues that need attention

## If Any Step Fails
Stop and fix the issue before proceeding. Do not commit broken code.
