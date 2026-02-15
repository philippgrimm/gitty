import './bootstrap';

// Highlight.js for diff syntax highlighting
import hljs from 'highlight.js/lib/core';

// Register commonly used languages
import php from 'highlight.js/lib/languages/php';
import javascript from 'highlight.js/lib/languages/javascript';
import typescript from 'highlight.js/lib/languages/typescript';
import css from 'highlight.js/lib/languages/css';
import xml from 'highlight.js/lib/languages/xml'; // HTML
import python from 'highlight.js/lib/languages/python';
import go from 'highlight.js/lib/languages/go';
import rust from 'highlight.js/lib/languages/rust';
import ruby from 'highlight.js/lib/languages/ruby';
import json from 'highlight.js/lib/languages/json';
import yaml from 'highlight.js/lib/languages/yaml';
import markdown from 'highlight.js/lib/languages/markdown';
import bash from 'highlight.js/lib/languages/bash';
import sql from 'highlight.js/lib/languages/sql';
import java from 'highlight.js/lib/languages/java';
import csharp from 'highlight.js/lib/languages/csharp';
import cpp from 'highlight.js/lib/languages/cpp';
import c from 'highlight.js/lib/languages/c';
import scss from 'highlight.js/lib/languages/scss';

hljs.registerLanguage('php', php);
hljs.registerLanguage('javascript', javascript);
hljs.registerLanguage('typescript', typescript);
hljs.registerLanguage('css', css);
hljs.registerLanguage('html', xml);
hljs.registerLanguage('xml', xml);
hljs.registerLanguage('python', python);
hljs.registerLanguage('go', go);
hljs.registerLanguage('rust', rust);
hljs.registerLanguage('ruby', ruby);
hljs.registerLanguage('json', json);
hljs.registerLanguage('yaml', yaml);
hljs.registerLanguage('markdown', markdown);
hljs.registerLanguage('bash', bash);
hljs.registerLanguage('sql', sql);
hljs.registerLanguage('java', java);
hljs.registerLanguage('csharp', csharp);
hljs.registerLanguage('cpp', cpp);
hljs.registerLanguage('c', c);
hljs.registerLanguage('scss', scss);
// Register aliases
hljs.registerLanguage('jsx', javascript);
hljs.registerLanguage('tsx', typescript);

// Function to highlight diff content
function highlightDiffContent() {
    document.querySelectorAll('.diff-file[data-language]').forEach(fileEl => {
        const language = fileEl.dataset.language;
        if (language === 'text') return; // Skip plain text
        
        fileEl.querySelectorAll('.line-content').forEach(lineEl => {
            // Skip if already highlighted
            if (lineEl.dataset.highlighted) return;
            
            const text = lineEl.textContent;
            if (!text.trim()) return;
            
            try {
                const result = hljs.highlight(text, { language, ignoreIllegals: true });
                lineEl.innerHTML = result.value;
                lineEl.dataset.highlighted = 'true';
            } catch (e) {
                // Silently fail — keep plain text
            }
        });
    });
}

// Run on initial page load
document.addEventListener('DOMContentLoaded', highlightDiffContent);

// Run after Livewire updates the DOM
document.addEventListener('livewire:navigated', highlightDiffContent);

// Hook into Livewire's morphdom to re-highlight after component updates
if (typeof Livewire !== 'undefined') {
    Livewire.hook('morph.updated', ({ el }) => {
        if (el.closest('.diff-container') || el.classList?.contains('diff-container')) {
            // Use requestAnimationFrame to ensure DOM is settled
            requestAnimationFrame(highlightDiffContent);
        }
    });
}

// Also listen for the custom event that Livewire dispatches
document.addEventListener('livewire:init', () => {
    Livewire.hook('morph.updated', ({ el }) => {
        if (el.closest('.diff-container') || el.classList?.contains('diff-container')) {
            requestAnimationFrame(highlightDiffContent);
        }
    });
});
