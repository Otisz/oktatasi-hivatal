import { useQuery } from '@tanstack/vue-query'
import { http } from '@/lib/http'
import type { ApiResponse, Applicant } from '@/types/api'

export function useApplicants() {
  return useQuery<Applicant[]>({
    queryKey: ['applicants'],
    queryFn: async () => {
      const { data } = await http.get<ApiResponse<Applicant[]>>('/api/v1/applicants')
      return data.data
    },
  })
}
