# Known Bugs

This file tracks known bugs in the tree management application.

## Bug #1: Tree Node Spacing Issue

**Status:** Open  
**Priority:** Medium  
**Date Reported:** 2025-01-15  
**Reporter:** User  

### Description
There is a visual spacing bug in the tree rendering where spacing between branches increases when more child nodes are added to a branch, even when the additional space is not needed. The extra space appears to be added only to the left side of the nodes.

### Reproduction Steps
1. Navigate to any tree view in edit mode
2. Observe a branch with multiple child nodes
3. Add additional child nodes to the branch
4. Notice that the spacing between branches increases disproportionately
5. The spacing issue becomes more pronounced with more child nodes

### Expected Behavior
- Spacing between tree branches should remain consistent regardless of the number of child nodes
- Additional child nodes should not affect the spacing of sibling branches
- Visual layout should remain clean and organized

### Actual Behavior  
- Spacing between branches increases as more child nodes are added
- Extra space is added primarily to the left side
- Layout becomes progressively more spaced out and less visually appealing

### Technical Details
- **Affected Files:** 
  - `app/src/Infrastructure/Rendering/EditTreeCssProvider.php` (CSS styling)
  - `app/src/Infrastructure/Rendering/StandardTreeCssProvider.php` (CSS styling)
  - Potentially `app/src/Domain/Tree/HtmlTreeNodeRenderer.php` (HTML structure)

- **CSS Areas of Interest:**
  - Tree li element spacing and padding
  - Flexbox layout properties
  - Connecting line CSS that may be affecting layout

### Investigation History
- **2025-01-15:** Multiple CSS approaches attempted:
  - Added explicit flex control (`justify-content: flex-start`, `align-items: flex-start`)
  - Changed to `flex: 0 0 auto`
  - Modified connecting line CSS
  - Added width constraints and absolute positioning
  - Switched to inline-block layout
- **Result:** All approaches either had no effect or completely broke the layout
- **Decision:** Reverted all changes, deferred fix to maintain working state

### Root Cause Analysis
- **Hypothesis:** The issue likely stems from CSS flexbox or positioning rules that accumulate spacing based on the depth or number of child elements
- **Investigation Needed:** 
  - Detailed analysis of how CSS connecting lines interact with node positioning
  - Understanding of the relationship between parent-child node rendering and spacing calculation
  - Review of whether the issue is in CSS rules or HTML structure generation

### Workaround
- No workaround currently available
- Issue is cosmetic and does not affect functionality
- Tree operations (add, delete, sort) work correctly despite spacing issue

### Notes
- This appears to be a pre-existing bug, not related to recent sort functionality additions
- The bug affects the visual presentation but not the core functionality
- Sort icons (< and >) were successfully added with proper spacing adjustments
- All tree operations continue to work normally

### Next Steps
1. Create isolated test case with minimal tree structure to reproduce issue
2. Systematically analyze CSS rules affecting tree layout
3. Consider alternative CSS approaches (Grid layout, different flexbox configuration)
4. Test potential fixes in isolated environment before applying to main codebase
5. Ensure any fixes maintain compatibility with sort icon positioning

---

## Future Bug Reports

**Format for new bugs:**
```markdown
## Bug #X: [Brief Description]

**Status:** [Open/In Progress/Resolved]  
**Priority:** [Low/Medium/High/Critical]  
**Date Reported:** YYYY-MM-DD  
**Reporter:** [Name/Role]  

### Description
[Detailed description of the bug]

### Reproduction Steps
1. [Step 1]
2. [Step 2]
...

### Expected Behavior
[What should happen]

### Actual Behavior
[What actually happens]

### Technical Details
[File paths, error messages, etc.]

### Workaround
[If any workaround exists]
```