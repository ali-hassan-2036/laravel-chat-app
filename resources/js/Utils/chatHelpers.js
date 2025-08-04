export const statusIcons = {
    sending: `<svg xmlns='http://www.w3.org/2000/svg' width='18' height='18' fill='none' viewBox='0 0 24 24' class='msg-status-icon msg-status-sending'><circle cx='12' cy='12' r='10' stroke='currentColor' stroke-width='2' fill='none'/><path d='M12 6v6l4 2' stroke='currentColor' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'/></svg>`,
    sent: `<svg xmlns='http://www.w3.org/2000/svg' width='18' height='18' fill='none' viewBox='0 0 24 24' class='msg-status-icon msg-status-sent'><path d='M20 6L9 17l-5-5' stroke='currentColor' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'/></svg>`,
    delivered: `<svg xmlns='http://www.w3.org/2000/svg' width='18' height='18' fill='none' viewBox='0 0 24 24' class='msg-status-icon msg-status-delivered'><path d='M17 7l-7.5 7.5-3.5-3.5' stroke='currentColor' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'/><path d='M22 7l-7.5 7.5-1.5-1.5' stroke='currentColor' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'/></svg>`,
    read: `<svg xmlns='http://www.w3.org/2000/svg' width='18' height='18' fill='none' viewBox='0 0 24 24' class='msg-status-icon msg-status-read'><path d='M17 7l-7.5 7.5-3.5-3.5' stroke='#3b82f6' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'/><path d='M22 7l-7.5 7.5-1.5-1.5' stroke='#3b82f6' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'/></svg>`,
    failed: `<svg xmlns='http://www.w3.org/2000/svg' width='18' height='18' fill='none' viewBox='0 0 24 24' class='msg-status-icon msg-status-failed'><circle cx='12' cy='12' r='10' stroke='#ef4444' stroke-width='2'/><line x1='15' y1='9' x2='9' y2='15' stroke='#ef4444' stroke-width='2'/><line x1='9' y1='9' x2='15' y2='15' stroke='#ef4444' stroke-width='2'/></svg>`
}

export const getMessageStatus = (msg, authUserId) => {
    if (msg.sender_id !== authUserId) return null;
    
    // Check for failed status first
    if (msg.status === 'failed') return 'failed';
    
    // Check read status
    if (msg.is_read) return 'read';
    
    // Check delivered status  
    if (msg.delivered_at) return 'delivered';
    
    // Check explicit status
    if (msg.status === 'sent') return 'sent';
    if (msg.status === 'sending') return 'sending';
    
    // Default fallback
    return 'sent';
}