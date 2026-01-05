# Fix Build Errors

Diagnose and fix TypeScript/build errors in the React portal.

## Steps

1. Run TypeScript compiler to see all errors:
   ```bash
   cd france-relocation-member-tools/portal && npx tsc --noEmit 2>&1
   ```

2. For each error, analyze and fix:
   - **Missing type property**: Add to `types/index.ts`
   - **Unused import**: Remove the import
   - **Type mismatch**: Update the type or cast appropriately
   - **Missing export**: Add export to the source file

3. After fixing, verify:
   ```bash
   npx tsc --noEmit
   ```

4. Run full build to confirm:
   ```bash
   npm run build
   ```

5. Report what was fixed.

## Common Error Patterns

| Error | Solution |
|-------|----------|
| `Property 'X' does not exist on type` | Add property to interface in `types/index.ts` |
| `'X' is declared but never used` | Remove the unused import/variable |
| `Type 'X' is not assignable to type 'Y'` | Fix type mismatch or add proper type assertion |
| `Cannot find module` | Check import path, might need `@/` alias |
