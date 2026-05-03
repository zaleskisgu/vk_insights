/**
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
    if (!Number.isFinite(o) || !Number.isFinite(p) || p <= 0) {
        return null;
    }
    return `https://vk.com/wall${o}_${p}`;
}
