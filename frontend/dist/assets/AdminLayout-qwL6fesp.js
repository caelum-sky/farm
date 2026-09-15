import{c as s,j as e,U as o,F as r,N as c,O as d}from"./index-CJ65lPXO.js";import"./firebase-BWJY1lKW.js";/**
 * @license lucide-react v0.446.0 - ISC
 *
 * This source code is licensed under the ISC license.
 * See the LICENSE file in the root directory of this source tree.
 */const h=s("ChartColumn",[["path",{d:"M3 3v16a2 2 0 0 0 2 2h16",key:"c24i48"}],["path",{d:"M18 17V9",key:"2bz60n"}],["path",{d:"M13 17V5",key:"1frdt8"}],["path",{d:"M8 17v-3",key:"17ska0"}]]);/**
 * @license lucide-react v0.446.0 - ISC
 *
 * This source code is licensed under the ISC license.
 * See the LICENSE file in the root directory of this source tree.
 */const p=s("LayoutList",[["rect",{width:"7",height:"7",x:"3",y:"3",rx:"1",key:"1g98yp"}],["rect",{width:"7",height:"7",x:"3",y:"14",rx:"1",key:"1bb6yr"}],["path",{d:"M14 4h7",key:"3xa0d5"}],["path",{d:"M14 9h7",key:"1icrd9"}],["path",{d:"M14 15h7",key:"1mj8o2"}],["path",{d:"M14 20h7",key:"11slyb"}]]);/**
 * @license lucide-react v0.446.0 - ISC
 *
 * This source code is licensed under the ISC license.
 * See the LICENSE file in the root directory of this source tree.
 */const m=s("Receipt",[["path",{d:"M4 2v20l2-1 2 1 2-1 2 1 2-1 2 1 2-1 2 1V2l-2 1-2-1-2 1-2-1-2 1-2-1-2 1Z",key:"q3az6g"}],["path",{d:"M16 8h-6a2 2 0 1 0 0 4h4a2 2 0 1 1 0 4H8",key:"1h4pet"}],["path",{d:"M12 17.5v-11",key:"1jc1ny"}]]),x=[{to:"/admin",label:"Overview",icon:h,end:!0},{to:"/admin/users",label:"Users",icon:o,end:!1},{to:"/admin/listings",label:"Listings",icon:p,end:!1},{to:"/admin/reports",label:"Reports",icon:r,end:!1},{to:"/admin/transactions",label:"Transactions",icon:m,end:!1}];function u(){return e.jsxs("div",{className:"shell section-y",children:[e.jsxs("header",{children:[e.jsx("h1",{className:"text-section text-canopy",children:"Moderation"}),e.jsx("p",{className:"mt-2 text-soil/70",children:"Everything here is logged against your account. Bans take effect immediately and end the member's active sessions."})]}),e.jsxs("div",{className:"mt-10 grid gap-8 lg:grid-cols-[220px_1fr]",children:[e.jsx("nav",{"aria-label":"Admin sections",children:e.jsx("ul",{className:"scroll-row lg:flex-col lg:gap-1",children:x.map(({to:a,label:t,icon:i,end:n})=>e.jsx("li",{children:e.jsxs(c,{to:a,end:n,className:({isActive:l})=>`flex items-center gap-2.5 whitespace-nowrap rounded-full px-4 py-2.5 text-sm transition duration-300 ease-grow ${l?"bg-canopy text-husk":"text-soil/70 hover:bg-soil/5 hover:text-soil"}`,children:[e.jsx(i,{size:17}),t]})},a))})}),e.jsx("div",{className:"min-w-0",children:e.jsx(d,{})})]})]})}export{u as default};
