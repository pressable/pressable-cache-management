# Pressable Cache Management — Developer Notes

## GitHub Auto-Updates

This plugin uses [YahnisElsts/plugin-update-checker v5.6](https://github.com/YahnisElsts/plugin-update-checker)
to deliver automatic updates directly from GitHub to WordPress sites running the plugin.

The library is **already bundled** at `includes/plugin-update-checker/` — no Composer or manual steps needed.
Only the files required for GitHub plugin updates are included (32 files). Excluded: DebugBar UI,
Theme update support, BitBucket/GitLab APIs.

---

## How to release an update

1. Bump `Version:` in `pressable-cache-management.php` — e.g. `5.2.4`
2. Update the `readme.txt` changelog
3. Commit and push to `main`
4. In GitHub → **Releases → Draft a new release**
5. Tag: `v5.2.4` (with the `v` prefix, matching the version number)
6. Attach the plugin `.zip` as a release asset
7. Publish

WordPress sites detect the new version within ~12 hours and show the standard
update notice. Clicking it installs exactly like a wordpress.org plugin.
