import{c as h}from"./main-DlVSwJCE.js";import{j as r}from"./vendor-query-CCQWzQLk.js";import{r as g}from"./vendor-react-BNGOmyOO.js";import{u as k}from"./vendor-virtual-CKQUPkvd.js";import{a as m}from"./vendor-utils-C-JT--wB.js";/**
 * @license lucide-react v0.303.0 - ISC
 *
 * This source code is licensed under the ISC license.
 * See the LICENSE file in the root directory of this source tree.
 */const S=h("LayoutGrid",[["rect",{width:"7",height:"7",x:"3",y:"3",rx:"1",key:"1g98yp"}],["rect",{width:"7",height:"7",x:"14",y:"3",rx:"1",key:"6d4xhi"}],["rect",{width:"7",height:"7",x:"14",y:"14",rx:"1",key:"nxv5o0"}],["rect",{width:"7",height:"7",x:"3",y:"14",rx:"1",key:"1bb6yr"}]]);/**
 * @license lucide-react v0.303.0 - ISC
 *
 * This source code is licensed under the ISC license.
 * See the LICENSE file in the root directory of this source tree.
 */const T=h("LayoutList",[["rect",{width:"7",height:"7",x:"3",y:"3",rx:"1",key:"1g98yp"}],["rect",{width:"7",height:"7",x:"3",y:"14",rx:"1",key:"1bb6yr"}],["path",{d:"M14 4h7",key:"3xa0d5"}],["path",{d:"M14 9h7",key:"1icrd9"}],["path",{d:"M14 15h7",key:"1mj8o2"}],["path",{d:"M14 20h7",key:"11slyb"}]]);/**
 * @license lucide-react v0.303.0 - ISC
 *
 * This source code is licensed under the ISC license.
 * See the LICENSE file in the root directory of this source tree.
 */const E=h("MoreVertical",[["circle",{cx:"12",cy:"12",r:"1",key:"41hilf"}],["circle",{cx:"12",cy:"5",r:"1",key:"gxeob9"}],["circle",{cx:"12",cy:"19",r:"1",key:"lyex9k"}]]);/**
 * @license lucide-react v0.303.0 - ISC
 *
 * This source code is licensed under the ISC license.
 * See the LICENSE file in the root directory of this source tree.
 */const R=h("Tag",[["path",{d:"M12 2H2v10l9.29 9.29c.94.94 2.48.94 3.42 0l6.58-6.58c.94-.94.94-2.48 0-3.42L12 2Z",key:"14b2ls"}],["path",{d:"M7 7h.01",key:"7u93v4"}]]);function G({items:t,renderItem:n,estimateSize:d,className:i,overscan:s=5,getItemKey:o}){const c=g.useRef(null),a=k({count:t.length,getScrollElement:()=>c.current,estimateSize:()=>d,overscan:s,getItemKey:o?e=>o(t[e],e):void 0}),l=a.getVirtualItems();return t.length===0?null:r.jsx("div",{ref:c,className:m("overflow-auto",i),style:{contain:"strict"},children:r.jsx("div",{style:{height:`${a.getTotalSize()}px`,width:"100%",position:"relative"},children:l.map(e=>r.jsx("div",{style:{position:"absolute",top:0,left:0,width:"100%",transform:`translateY(${e.start}px)`},"data-index":e.index,children:n(t[e.index],e.index)},e.key))})})}function C({items:t,renderItem:n,estimateSize:d,columns:i,gap:s=16,className:o,overscan:c=2,getItemKey:a}){const l=g.useRef(null),e=Math.ceil(t.length/i),p=k({count:e,getScrollElement:()=>l.current,estimateSize:()=>d+s,overscan:c}),v=p.getVirtualItems();return t.length===0?null:r.jsx("div",{ref:l,className:m("overflow-auto",o),style:{contain:"strict"},children:r.jsx("div",{style:{height:`${p.getTotalSize()}px`,width:"100%",position:"relative"},children:v.map(x=>{const y=x.index*i,w=t.slice(y,y+i);return r.jsx("div",{style:{position:"absolute",top:0,left:0,width:"100%",transform:`translateY(${x.start}px)`,display:"grid",gridTemplateColumns:`repeat(${i}, minmax(0, 1fr))`,gap:`${s}px`,paddingBottom:`${s}px`},children:w.map((f,b)=>{const u=y+b,j=a?a(f,u):u;return r.jsx("div",{children:n(f,u)},j)})},x.key)})})})}function I(t,n=50){return t>n}export{T as L,E as M,R as T,G as V,S as a,C as b,I as u};
