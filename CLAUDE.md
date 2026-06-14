# CLAUDE.md — Project Guidelines for Claude Code

## Git / Workflow

CRITICAL: Always commit directly to main. Never create branches.
Never create pull requests. Use:
```
git checkout main && git pull && git add -A && git commit -m '...' && git push origin main
```

## Project Structure

- `frontend/` — Vue 3 SPA (Vite, Vue Router, Pinia, SCSS)
- `backend/` — Laravel API
- Docker-based deployment via `docker.yml` CI (triggers on push to `main` or `v*` tags)
