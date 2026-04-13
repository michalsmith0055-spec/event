import { useEffect, useState } from 'react';
import { api } from '../lib/api';
export function DraftsPage(){const [rows,setRows]=useState<any[]>([]);useEffect(()=>{api.get('/drafts').then(r=>setRows(r.data));},[]);return <div><h1 className='text-2xl font-bold'>Drafts</h1><table className='w-full text-sm'><thead><tr><th>Title</th><th>Board</th><th>Link</th><th>Status</th></tr></thead><tbody>{rows.map(r=><tr key={r.id}><td contentEditable suppressContentEditableWarning>{r.title}</td><td>{r.board?.name||'-'}</td><td>{r.destinationLink}</td><td>{r.status}</td></tr>)}</tbody></table></div>}
