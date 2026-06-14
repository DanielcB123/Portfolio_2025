import { useForm, usePage } from '@inertiajs/vue3'
import { appRoute } from '@/utils/appRoute'

export function useLoginForm(options = {}) {
  const { redirect = null } = options
  const page = usePage()

  const form = useForm({
    email: '',
    password: '',
    remember: false,
    ...(redirect ? { redirect } : {}),
  })

  const fillDemoUser = (user, demoPassword) => {
    form.email = user.email
    form.password = demoPassword
  }

  const submit = () => {
    form.post(appRoute('login'), {
      onSuccess: () => {
        const user = page.props.auth?.user
        if (user?.api_key) {
          localStorage.setItem('api_key', user.api_key)
        }
      },
      onFinish: () => form.reset('password'),
    })
  }

  return { form, fillDemoUser, submit }
}
