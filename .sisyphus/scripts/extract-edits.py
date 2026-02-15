#!/usr/bin/env python3
"""
Extract all edit operations from OpenCode session transcripts.
Parses the tool call format and extracts filePath, oldString, newString, replaceAll, timestamp.
"""

import re
import json
from pathlib import Path
from datetime import datetime

def parse_timestamp(ts_str):
    """Parse ISO timestamp from session messages."""
    try:
        return datetime.fromisoformat(ts_str.replace('Z', '+00:00'))
    except:
        return None

def extract_edits_from_transcript(transcript_path, cutoff_time=None):
    """
    Extract all edit operations from a transcript file.
    
    Args:
        transcript_path: Path to the transcript file
        cutoff_time: Optional datetime - only include edits before this time
    
    Returns:
        List of edit operations with metadata
    """
    edits = []
    
    with open(transcript_path, 'r', encoding='utf-8') as f:
        content = f.read()
    
    # Split into messages
    messages = re.split(r'\n\[(?:user|assistant)', content)
    
    current_timestamp = None
    
    for msg in messages:
        # Extract timestamp from message header
        ts_match = re.search(r'\((?:sisyphus|prometheus|atlas)\)\] (\d{4}-\d{2}-\d{2}T[\d:.]+Z)', msg)
        if ts_match:
            current_timestamp = parse_timestamp(ts_match.group(1))
            
            # Check cutoff
            if cutoff_time and current_timestamp and current_timestamp > cutoff_time:
                continue
        
        # Find all [tool: edit] blocks
        edit_blocks = re.finditer(r'\[tool: edit\]\s*\n', msg)
        
        for match in edit_blocks:
            # Extract the tool call parameters after [tool: edit]
            start_pos = match.end()
            
            # Find the next tool call or end of message
            next_tool = re.search(r'\n\[tool:', msg[start_pos:])
            if next_tool:
                block = msg[start_pos:start_pos + next_tool.start()]
            else:
                block = msg[start_pos:]
            
            # Try to extract filePath, oldString, newString, replaceAll
            # The format is typically on separate lines
            file_match = re.search(r'filePath["\s:]+([^\n"]+)', block)
            old_match = re.search(r'oldString["\s:]+(.+?)(?=newString|replaceAll|$)', block, re.DOTALL)
            new_match = re.search(r'newString["\s:]+(.+?)(?=replaceAll|$)', block, re.DOTALL)
            replace_all_match = re.search(r'replaceAll["\s:]+(\w+)', block)
            
            if file_match and old_match and new_match:
                edit = {
                    'timestamp': current_timestamp.isoformat() if current_timestamp else None,
                    'filePath': file_match.group(1).strip().strip('"'),
                    'oldString': old_match.group(1).strip().strip('"'),
                    'newString': new_match.group(1).strip().strip('"'),
                    'replaceAll': replace_all_match.group(1).lower() == 'true' if replace_all_match else False
                }
                edits.append(edit)
    
    return edits

def main():
    # Session 1: ses_3a94c1f51ffewu9qKb1evf0hKW (full session)
    session1_path = Path('/Users/philipp.grimm/.local/share/opencode/tool-output/tool_c5cee4f82001iKDv9gGOlOQYVb')
    
    # Session 2: ses_3a825d1bdffeNBD6XobOBmNqQr (only before 18:37:19 UTC+1 = 17:37:19 UTC)
    session2_path = Path('/Users/philipp.grimm/.local/share/opencode/tool-output/tool_c5ceed480001Gfiee2r8BZi1Q8')
    cutoff = datetime.fromisoformat('2026-02-13T17:37:19+00:00')
    
    print("Extracting edits from session 1...")
    edits1 = extract_edits_from_transcript(session1_path)
    print(f"Found {len(edits1)} edits in session 1")
    
    print("\nExtracting edits from session 2 (before 18:37:19)...")
    edits2 = extract_edits_from_transcript(session2_path, cutoff_time=cutoff)
    print(f"Found {len(edits2)} edits in session 2")
    
    # Combine and sort by timestamp
    all_edits = edits1 + edits2
    all_edits.sort(key=lambda e: e['timestamp'] if e['timestamp'] else '')
    
    print(f"\nTotal edits: {len(all_edits)}")
    
    # Save to JSON for inspection
    output_path = Path('.sisyphus/drafts/extracted-edits.json')
    output_path.parent.mkdir(parents=True, exist_ok=True)
    
    with open(output_path, 'w') as f:
        json.dump(all_edits, f, indent=2)
    
    print(f"\nSaved to {output_path}")
    
    # Print summary
    files = set(e['filePath'] for e in all_edits)
    print(f"\nFiles affected: {len(files)}")
    for f in sorted(files):
        count = sum(1 for e in all_edits if e['filePath'] == f)
        print(f"  {f}: {count} edits")

if __name__ == '__main__':
    main()
