import About from '../components/landing/About'
import Contact from '../components/landing/Contact'
import CtaBanner from '../components/landing/CtaBanner'
import Faq from '../components/landing/Faq'
import Features from '../components/landing/Features'
import Hero from '../components/landing/Hero'
import HowItWorks from '../components/landing/HowItWorks'
import Testimonials from '../components/landing/Testimonials'
import UserCategories from '../components/landing/UserCategories'

export default function Landing() {
  return (
    <>
      <Hero />
      <Features />
      <HowItWorks />
      <UserCategories />
      <About />
      <Testimonials />
      <Faq />
      <CtaBanner />
      <Contact />
    </>
  )
}
