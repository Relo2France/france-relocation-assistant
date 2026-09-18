import{c as d}from"./main-CHifsAR9.js";import{r as f,j as r}from"./vendor-query-aXLgDoqj.js";import{u as m}from"./vendor-virtual-Bx84C3U3.js";import{a as k}from"./vendor-utils-hDeTKz2a.js";/**
 * @license lucide-react v0.303.0 - ISC
 *
 * This source code is licensed under the ISC license.
 * See the LICENSE file in the root directory of this source tree.
 */const $=d("Flag",[["path",{d:"M4 15s1-1 4-1 5 2 8 2 4-1 4-1V3s-1 1-4 1-5-2-8-2-4 1-4 1z",key:"i9b6wo"}],["line",{x1:"4",x2:"4",y1:"22",y2:"15",key:"1cm3nv"}]]);/**
 * @license lucide-react v0.303.0 - ISC
 *
 * This source code is licensed under the ISC license.
 * See the LICENSE file in the root directory of this source tree.
 */const S=d("LayoutGrid",[["rect",{width:"7",height:"7",x:"3",y:"3",rx:"1",key:"1g98yp"}],["rect",{width:"7",height:"7",x:"14",y:"3",rx:"1",key:"6d4xhi"}],["rect",{width:"7",height:"7",x:"14",y:"14",rx:"1",key:"nxv5o0"}],["rect",{width:"7",height:"7",x:"3",y:"14",rx:"1",key:"1bb6yr"}]]);/**
 * @license lucide-react v0.303.0 - ISC
 *
 * This source code is licensed under the ISC license.
 * See the LICENSE file in the root directory of this source tree.
 */const T=d("LayoutList",[["rect",{width:"7",height:"7",x:"3",y:"3",rx:"1",key:"1g98yp"}],["rect",{width:"7",height:"7",x:"3",y:"14",rx:"1",key:"1bb6yr"}],["path",{d:"M14 4h7",key:"3xa0d5"}],["path",{d:"M14 9h7",key:"1icrd9"}],["path",{d:"M14 15h7",key:"1mj8o2"}],["path",{d:"M14 20h7",key:"11slyb"}]]);/**
 * @license lucide-react v0.303.0 - ISC
 *
 * This source code is licensed under the ISC license.
 * See the LICENSE file in the root directory of this source tree.
 */const E=d("Tag",[["path",{d:"M12 2H2v10l9.29 9.29c.94.94 2.48.94 3.42 0l6.58-6.58c.94-.94.94-2.48 0-3.42L12 2Z",key:"14b2ls"}],["path",{d:"M7 7h.01",key:"7u93v4"}]]);function R({items:t,renderItem:n,estimateSize:h,className:i,overscan:s=5,getItemKey:o}){const l=f.useRef(null),a=m({count:t.length,getScrollElement:()=>l.current,estimateSize:()=>h,overscan:s,getItemKey:o?e=>o(t[e],e):void 0}),c=a.getVirtualItems();return t.length===0?null:r.jsx("div",{ref:l,className:k("overflow-auto",i),style:{contain:"strict"},children:r.jsx("div",{style:{height:`${a.getTotalSize()}px`,width:"100%",position:"relative"},children:c.map(e=>r.jsx("div",{style:{position:"absolute",top:0,left:0,width:"100%",transform:`translateY(${e.start}px)`},"data-index":e.index,children:n(t[e.index],e.index)},e.key))})})}function F({items:t,renderItem:n,estimateSize:h,columns:i,gap:s=16,className:o,overscan:l=2,getItemKey:a}){const c=f.useRef(null),e=Math.ceil(t.length/i),p=m({count:e,getScrollElement:()=>c.current,estimateSize:()=>h+s,overscan:l}),v=p.getVirtualItems();return t.length===0?null:r.jsx("div",{ref:c,className:k("overflow-auto",o),style:{contain:"strict"},children:r.jsx("div",{style:{height:`${p.getTotalSize()}px`,width:"100%",position:"relative"},children:v.map(x=>{const y=x.index*i,w=t.slice(y,y+i);return r.jsx("div",{style:{position:"absolute",top:0,left:0,width:"100%",transform:`translateY(${x.start}px)`,display:"grid",gridTemplateColumns:`repeat(${i}, minmax(0, 1fr))`,gap:`${s}px`,paddingBottom:`${s}px`},children:w.map((g,b)=>{const u=y+b,j=a?a(g,u):u;return r.jsx("div",{children:n(g,u)},j)})},x.key)})})})}function G(t,n=50){return t>n}export{$ as F,T as L,E as T,R as V,S as a,F as b,G as u};
