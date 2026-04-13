import { Link, Outlet } from 'react-router-dom';
const links = ['Dashboard','Connect Pinterest','Boards','Imports','Drafts','Schedule','Analytics','Settings','Compliance Center'];
export function Layout(){return <div className='min-h-screen flex'><aside className='w-64 p-4 bg-slate-900 text-white'>{links.map(l=><div key={l}><Link to={`/${l.toLowerCase().replace(/ /g,'-')}`}>{l}</Link></div>)}</aside><main className='p-6 flex-1'><Outlet/></main></div>}
