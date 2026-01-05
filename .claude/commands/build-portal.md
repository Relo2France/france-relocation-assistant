# Build and Verify Portal

Build the React portal and verify everything compiles correctly.

## Steps

1. Change to the portal directory:
   ```bash
   cd france-relocation-member-tools/portal
   ```

2. Run TypeScript type checking:
   ```bash
   npx tsc --noEmit
   ```

3. If there are TypeScript errors, fix them before proceeding.

4. Run the production build:
   ```bash
   npm run build
   ```

5. If the build succeeds, report what files were generated.

6. If there are errors, analyze them and suggest fixes.

## Success Criteria
- TypeScript compiles with no errors
- Vite build completes successfully
- All chunks are generated in `assets/portal/`
