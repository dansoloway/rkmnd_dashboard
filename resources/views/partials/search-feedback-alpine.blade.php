@once
<script>
document.addEventListener('alpine:init', () => {
    Alpine.data('searchFeedbackPanel', (config) => ({
        searchId: config.searchId,
        feedbackUrl: config.feedbackUrl,
        csrf: config.csrf,
        source: config.source || 'dashboard',
        votes: {},
        busy: false,
        error: '',
        voteKey(wpPostId) {
            return wpPostId === null || wpPostId === undefined ? 'query' : String(wpPostId);
        },
        voteFor(wpPostId) {
            return this.votes[this.voteKey(wpPostId)] ?? null;
        },
        async submit(vote, wpPostId, rank, pineconeScore) {
            this.error = '';
            const key = this.voteKey(wpPostId);
            if (this.votes[key] === vote) {
                return;
            }
            this.busy = true;
            const body = {
                search_id: this.searchId,
                vote: vote,
                source: this.source,
            };
            if (wpPostId !== null && wpPostId !== undefined) {
                body.wp_post_id = wpPostId;
            }
            if (rank !== null && rank !== undefined) {
                body.rank = rank;
            }
            if (pineconeScore !== null && pineconeScore !== undefined) {
                body.pinecone_score = pineconeScore;
            }
            try {
                const res = await fetch(this.feedbackUrl, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': this.csrf,
                    },
                    body: JSON.stringify(body),
                });
                const data = await res.json().catch(() => ({}));
                if (!res.ok || !data.ok) {
                    throw new Error(data.message || 'Feedback failed');
                }
                this.votes[key] = vote;
            } catch (e) {
                this.error = e.message || 'Could not save feedback';
            } finally {
                this.busy = false;
            }
        },
    }));
});
</script>
@endonce
