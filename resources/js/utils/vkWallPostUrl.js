/**
 * Каноническая ссылка на пост VK: `https://vk.com/wall{owner_id}_{post_id}`
 * (owner_id со знаком: отрицательный для сообществ, см. объекты Wall API).
 *
 * Не используем `/{screen_name}?w=…`: при рассинхроне slug и owner_id ссылка ведёт не туда.
 *
 * @param {unknown} ownerId
 * @param {unknown} postId
 * @returns {string | null}
 */
export function vkWallPostUrl(ownerId, postId) {
    if (ownerId == null || postId == null) {
        return null;
    }
    const o = Number(ownerId);
    const p = Number(postId);
    if (!Number.isFinite(o) || o === 0 || !Number.isFinite(p) || p <= 0) {
        return null;
    }
    return `https://vk.com/wall${o}_${p}`;
}
