<?php

namespace App\Services;

use App\Events\ListingFlagged;
use App\Models\FraudSignal;
use App\Models\MarketplaceItem;
use App\Models\SiteSetting;

class ModerationService
{
    public function flagListingsNeedingReview(): int
    {
        $settings = SiteSetting::allSettings();
        $keywords = collect(explode(',', $settings['flagged_keywords'] ?? ''))
            ->map(fn (string $keyword): string => trim(strtolower($keyword)))
            ->filter()
            ->values();

        $flagged = 0;

        MarketplaceItem::query()
            ->where('moderation_status', '!=', 'rejected')
            ->chunkById(100, function ($posts) use ($keywords, &$flagged): void {
                foreach ($posts as $post) {
                    $reason = $this->flagReason($post, $keywords);

                    if (! $reason) {
                        $this->refreshListingScore($post);
                        continue;
                    }

                    if ($post->flagged_reason !== $reason || $post->moderation_status !== 'pending') {
                        $post->update([
                            'flagged_reason' => $reason,
                            'moderation_status' => 'pending',
                            'lifecycle_status' => MarketplaceItem::LIFECYCLE_REVIEW,
                            'is_available' => false,
                            'listing_score' => min((int) $post->listing_score, 25),
                        ]);

                        FraudSignal::firstOrCreate(
                            [
                                'signalable_type' => $post->getMorphClass(),
                                'signalable_id' => $post->id,
                                'type' => 'listing_moderation',
                                'reason' => $reason,
                            ],
                            [
                                'user_id' => $post->owner_id,
                                'score' => $post->report_count > 3 ? 85 : 65,
                                'severity' => $post->report_count > 3 ? 'high' : 'medium',
                                'metadata' => [
                                    'report_count' => $post->report_count,
                                    'title' => $post->title,
                                ],
                            ],
                        );

                        event(new ListingFlagged($post, $reason));
                        $flagged++;
                    }
                }
            });

        return $flagged;
    }

    public function refreshListingScore(MarketplaceItem $post): int
    {
        $score = 75;
        $score -= min(40, (int) $post->report_count * 10);
        $score += $post->image_url ? 5 : 0;
        $score += $post->latitude && $post->longitude ? 5 : 0;
        $score += $post->owner?->kyc_status === 'verified' ? 10 : 0;
        $score = max(0, min(100, $score));

        if ((int) $post->listing_score !== $score) {
            $post->forceFill(['listing_score' => $score])->save();
        }

        return $score;
    }

    private function flagReason(MarketplaceItem $post, $keywords): ?string
    {
        if ($post->report_count > 3) {
            return 'Reported by more than 3 users.';
        }

        if ($keywords->isEmpty()) {
            return null;
        }

        $haystack = strtolower($post->title.' '.$post->description);
        $keyword = $keywords->first(fn (string $candidate): bool => str_contains($haystack, $candidate));

        return $keyword ? 'Matched flagged keyword: '.$keyword.'.' : null;
    }
}
