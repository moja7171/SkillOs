#!/bin/bash
# Rebuilds vendor/ inside the persistent `deploy` branch worktree, merges in
# whatever's new on main, commits, and pushes.
#
# One-time setup this depends on:
#   - a `deploy` branch + worktree at ~/skillos-deploy-worktree
#   - a GitHub push credential (personal access token) configured for
#     this machine's `git push` to work at all
#   - .cpanel.yml on that branch, and a cPanel Git Version Control repo
#     cloned from it (see README "Deploying to shared hosting")
#
# After running this, the update reaches the live site once you click
# "Update from Remote" / "Pull or Deploy" in cPanel's Git Version
# Control screen for this repo.
#
# public/build is already committed on main (no Node on the host), so unlike
# a typical Laravel deploy branch this one only ever needs a fresh vendor/.

set -euo pipefail

WORKTREE="/home/moja/skillos-deploy-worktree"

if [ ! -d "$WORKTREE" ]; then
    echo "Worktree not found at $WORKTREE — has it been removed?" >&2
    exit 1
fi

cd "$WORKTREE"
# Merges the LOCAL main branch, not origin/main — this worktree shares the
# same repo/refs as the main checkout, so local commits on main are already
# visible here without needing a push to GitHub first.
git merge main --no-edit

echo "--- composer install ---"
composer install --no-dev --optimize-autoloader --no-interaction

git add -Af vendor

if git diff --cached --quiet; then
    echo "vendor/ unchanged — nothing new to commit there."
else
    git commit -m "Deploy snapshot: refresh vendor"
fi

echo "--- pushing deploy branch ---"
git push origin deploy

echo
echo "Pushed. Now go click 'Update from Remote' (or 'Deploy HEAD Commit') in"
echo "cPanel's Git Version Control screen for this repo to actually update"
echo "the live site."
