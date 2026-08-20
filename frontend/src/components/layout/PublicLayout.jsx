import { Outlet, useLocation } from 'react-router-dom'
import Footer from './Footer'
import Navbar from './Navbar'

export default function PublicLayout() {
  const { pathname } = useLocation()

  // Only the landing page has a hero for the navbar to float over.
  const overHero = pathname === '/'

  return (
    <div className="flex min-h-dvh flex-col">
      <Navbar transparent={overHero} />
      <main className={overHero ? 'flex-1' : 'flex-1 pt-20'}>
        <Outlet />
      </main>
      <Footer />
    </div>
  )
}
