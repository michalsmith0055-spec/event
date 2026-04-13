import { useEffect, useState } from 'react';
import { api } from '../lib/api';
export function DashboardPage(){const [data,setData]=useState<any>();useEffect(()=>{api.get('/analytics/overview').then(r=>setData(r.data));},[]);return <div><h1 className='text-2xl font-bold'>Dashboard {import.meta.env.VITE_DEMO_MODE?'(Demo Mode)':''}</h1><pre>{JSON.stringify(data,null,2)}</pre></div>}
