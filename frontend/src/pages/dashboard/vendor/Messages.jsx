import PageHeader from '../../../components/ui/PageHeader'
import MessagesInbox from '../../../components/marketplace/MessagesInbox'

export default function Messages() {
  return (
    <div className="space-y-6">
      <PageHeader title="Messages" description="Your conversations with planners." />
      <MessagesInbox />
    </div>
  )
}
