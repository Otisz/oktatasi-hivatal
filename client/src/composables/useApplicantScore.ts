import { useQuery } from '@tanstack/vue-query'
import axios from 'axios'
import type { MaybeRefOrGetter } from 'vue'
import { computed, toValue } from 'vue'
import { http } from '@/lib/http'
import type { ApiError, ApiResponse, ScoreResult } from '@/types/api'

export type ScoreError = { kind: 'domain'; message: string } | { kind: 'generic' }

export function useApplicantScore(id: MaybeRefOrGetter<string>) {
  return useQuery<ScoreResult, ScoreError>({
    queryKey: computed(() => ['applicants', 'score', toValue(id)]),
    queryFn: async () => {
      try {
        const { data } = await http.get<ApiResponse<ScoreResult>>(`/api/v1/applicants/${toValue(id)}/score`)
        return data.data
      } catch (e) {
        if (axios.isAxiosError(e) && e.response?.status === 422) {
          const body = e.response.data as ApiError
          throw { kind: 'domain', message: body.error } satisfies ScoreError
        }
        throw { kind: 'generic' } satisfies ScoreError
      }
    },
    retry: (_, error) => error.kind !== 'domain',
  })
}
