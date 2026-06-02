#!/usr/bin/env sh
set -eu

ROOT="$(CDPATH= cd -- "$(dirname "$0")/.." && pwd)"
VERSION="$(php -r '$file = file_get_contents($argv[1]); if (!preg_match("/public const VERSION = '\''([^'\'']+)'\'';/", $file, $m)) { fwrite(STDERR, "Unable to read Typecho World version.\n"); exit(1); } echo $m[1];' "$ROOT/var/Typecho/Common.php")"
TAG="v$VERSION"
BRANCH="$(git -C "$ROOT" branch --show-current)"

if [ -z "$BRANCH" ]; then
  echo "Unable to detect current branch." >&2
  exit 1
fi

if ! git -C "$ROOT" diff --quiet || ! git -C "$ROOT" diff --cached --quiet; then
  echo "Please commit all changes before creating a release tag." >&2
  exit 1
fi

if git -C "$ROOT" rev-parse "$TAG" >/dev/null 2>&1; then
  echo "Tag $TAG already exists locally."
else
  git -C "$ROOT" tag -a "$TAG" -m "Typecho World $TAG"
fi

git -C "$ROOT" push origin "$BRANCH"
git -C "$ROOT" push origin "$TAG"

echo "Released Typecho World $TAG."
