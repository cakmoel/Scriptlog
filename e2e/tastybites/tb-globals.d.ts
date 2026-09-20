// tb-globals.d.ts
// Ambient global types for the TastyBites e2e suite. Augments the DOM Window
// with the two client-side globals the specs read from page.evaluate:
//  - CommentSettings: inlined by single.php before the comment scripts.
//  - jQuery: only the static _data() introspection used to wait for the
//    comment-form submit handler binding (plan finding R12) is typed here;
//    the DOM lib has no jQuery surface so this keeps page.evaluate callbacks
//    fully type-checked without installing @types/jquery.

interface Window {
  CommentSettings?: {
    postId?: number | string;
  };
  jQuery?: {
    _data?: (
      element: Element | null,
      dataKey: string,
    ) => { submit?: unknown } | null | undefined;
  };
}