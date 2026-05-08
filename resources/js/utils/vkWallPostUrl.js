/**
 * `https://vk.com/{screen_name}?w=wall{owner_id}_{post_id}` при известном screen_name, иначе `https://vk.com/wall…`.
 *
 * @param {unknown} ownerId
 * @param {unknown} postId
 * @param {unknown} [screenName] screen_name сообщества из meta / API
 * @returns {string | null}
 */
export function vkWallPostUrl(ownerId, postId, screenName) {
    if (ownerId == null || postId == null) {
        return null;
    }
    const o = Number(ownerId);
    const p = Number(postId);
    // owner_id из VK ненулевой (отрицательный для групп, положительный для пользователей)
    if (!Number.isFinite(o) || o === 0 || !Number.isFinite(p) || p <= 0) {
        return null;
    }
    const wallParam = `wall${o}_${p}`;
    const slug =
        screenName != null && String(screenName).trim() !== '' ? String(screenName).trim() : '';
    if (slug) {
        return `https://vk.com/${encodeURIComponent(slug)}?w=${encodeURIComponent(wallParam)}`;
    }
    return `https://vk.com/${wallParam}`;
}
