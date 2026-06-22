# CLAUDE.md — Project Guidelines for Claude Code

## Git / Workflow

CRITICAL: Always commit directly to main. Never create branches.
Never create pull requests. Use:
```
git checkout main && git pull && git add -A && git commit -m '...' && git push origin main
```

### Selbstprüfung am Ende JEDER Session

1. **Branch-Check vor jedem Commit:** `git branch --show-current` MUSS `main` ausgeben.
   Falls nicht: sofort `git checkout main` und Änderungen übertragen.

2. **Branch-Cleanup:** Falls aus irgendeinem Grund ein Branch entstanden ist, IMMER selbst
   nach main mergen (`git merge --ff-only <branch>`) und pushen, bevor die Session endet.
   Niemals einen unverarbeiteten Branch zurücklassen.

3. **Abschlussbericht:** Jede Session MUSS explizit einen dieser Sätze enthalten:
   - `✅ Auf main committet und gepusht`
   - `⚠️ Branch <name> muss noch gemerged werden`

## Project Structure

- `frontend/` — Vue 3 SPA (Vite, Vue Router, Pinia, SCSS)
- `backend/` — Laravel API
- Docker-based deployment via `docker.yml` CI (triggers on push to `main` or `v*` tags)
