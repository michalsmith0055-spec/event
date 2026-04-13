import { BrowserRouter, Navigate, Route, Routes } from 'react-router-dom';
import { Layout } from './components/Layout';
import { LoginPage } from './pages/LoginPage';
import { DashboardPage } from './pages/DashboardPage';
import { DraftsPage } from './pages/DraftsPage';
import { SimplePage } from './pages/SimplePage';

export default function App(){return <BrowserRouter><Routes><Route path='/' element={<LoginPage/>}/><Route element={<Layout/>}><Route path='/dashboard' element={<DashboardPage/>}/><Route path='/connect-pinterest' element={<SimplePage title='Connect Pinterest'/>}/><Route path='/boards' element={<SimplePage title='Boards'/>}/><Route path='/imports' element={<SimplePage title='Imports'/>}/><Route path='/drafts' element={<DraftsPage/>}/><Route path='/schedule' element={<SimplePage title='Schedule'/>}/><Route path='/analytics' element={<SimplePage title='Analytics'/>}/><Route path='/settings' element={<SimplePage title='Settings'/>}/><Route path='/compliance-center' element={<SimplePage title='Compliance Center'/>}/></Route><Route path='*' element={<Navigate to='/'/>}/></Routes></BrowserRouter>}
