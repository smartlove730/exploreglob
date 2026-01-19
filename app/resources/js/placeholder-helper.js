// Helper function to generate SVG data URI placeholders
function generateSVGPlaceholder(width, height, text = 'Image', bgColor = '6366f1', textColor = 'ffffff') {
    const svg = `<svg xmlns="http://www.w3.org/2000/svg" width="${width}" height="${height}">
        <rect fill="#${bgColor}" width="100%" height="100%"/>
        <text x="50%" y="50%" dominant-baseline="middle" text-anchor="middle" fill="#${textColor}" 
              font-family="Arial,sans-serif" font-size="${Math.min(width, height) / 15}" font-weight="600">${text}</text>
    </svg>`;
    return `data:image/svg+xml,${encodeURIComponent(svg)}`;
}

// Safe image error handler that uses SVG fallback
function handleImageError(img, text = 'Image') {
    // #region agent log
    const errorCount = parseInt(img.dataset.errorCount || '0') + 1;
    img.dataset.errorCount = errorCount;
    fetch('http://127.0.0.1:7242/ingest/ffb9fee4-cb58-4096-a251-97bedfe37eac',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({location:'placeholder-helper.js:13',message:'Image error handler called',data:{src:img.src,errorCount:errorCount,width:img.width||800,height:img.height||600},timestamp:Date.now(),sessionId:'debug-session',runId:'run1',hypothesisId:'D'})}).catch(()=>{});
    // #endregion
    
    // Prevent infinite loops - use SVG immediately
    if (errorCount > 1) {
        // #region agent log
        fetch('http://127.0.0.1:7242/ingest/ffb9fee4-cb58-4096-a251-97bedfe37eac',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({location:'placeholder-helper.js:18',message:'Using SVG fallback',data:{errorCount:errorCount},timestamp:Date.now(),sessionId:'debug-session',runId:'run1',hypothesisId:'E'})}).catch(()=>{});
        // #endregion
        return; // Already set to SVG
    }
    
    const width = img.naturalWidth || img.width || 800;
    const height = img.naturalHeight || img.height || 600;
    img.src = generateSVGPlaceholder(width, height, text);
    img.classList.add('image-placeholder');
    
    // #region agent log
    fetch('http://127.0.0.1:7242/ingest/ffb9fee4-cb58-4096-a251-97bedfe37eac',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({location:'placeholder-helper.js:28',message:'SVG placeholder applied',data:{width:width,height:height,text:text},timestamp:Date.now(),sessionId:'debug-session',runId:'run1',hypothesisId:'F'})}).catch(()=>{});
    // #endregion
}
