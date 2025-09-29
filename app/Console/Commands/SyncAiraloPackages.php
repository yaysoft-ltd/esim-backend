<?php

namespace App\Console\Commands;

use App\Jobs\CreateInAppProductJob;
use App\Models\Country;
use Illuminate\Console\Command;
use App\Services\AiraloService;
use App\Models\EsimPackage;
use App\Models\Operator;
use Illuminate\Support\Facades\DB;

class SyncAiraloPackages extends Command
{
    protected $signature = 'airalo:sync-packages';
    protected $description = 'Sync Airalo eSIM packages with local database';

    public function handle()
    {
        $this->info('Syncing Airalo packages...');

        try {
            $airaloService = new AiraloService();
            $count = 0;
            $page = 1;

            $airaloCountrySlugs = [];
            $airaloOperatorIds = [];
            $airaloPackageIds = [];

            do {
                $this->info("Fetching page $page...");
                $response = $airaloService->getPackages([
                    'page' => $page,
                    'type' => 'local',
                    'include' => 'topup',
                ]);

                if (empty($response['data'])) {
                    break;
                }

                foreach ($response['data'] as $data) {
                    $airaloCountrySlugs[] = $data['slug'];

                    // --- Country ---
                    $country = Country::firstOrNew(['slug' => $data['slug']]);
                    $countryAttrs = [
                        'slug' => $data['slug'],
                        'country_code' => $data['country_code'],
                        'name' => $data['title'],
                        'image' => $data['image']['url'] ?? null,
                        'is_active' => 1
                    ];
                    if ($country->exists) {
                        if (updateIfChanged($country, $countryAttrs)) {
                            $this->info("Updated country: {$country->name}");
                        }
                    } else {
                        $country->fill($countryAttrs)->save();
                        $this->info("Created country: {$country->name}");
                    }

                    // --- Operators ---
                    foreach ($data['operators'] as $operator) {
                        $generatedOperator = Operator::firstOrNew(['airaloOperatorId' => $operator['id']]);
                        $operatorAttrs = [
                            'airaloOperatorId' => $operator['id'],
                            'country_id' => $country->id,
                            'name' => $operator['title'],
                            'type' => $operator['type'],
                            'is_prepaid' => $operator['is_prepaid'],
                            'esim_type' => $operator['esim_type'],
                            'apn_type' => $operator['apn_type'],
                            'apn_value' => $operator['apn_value'],
                            'info' => implode("\n", $operator['info']),
                            'image' => $operator['image']['url'] ?? null,
                            'plan_type' => $operator['plan_type'],
                            'activation_policy' => $operator['activation_policy'],
                            'rechargeability' => $operator['rechargeability'],
                            'is_active' => 1
                        ];
                        if ($generatedOperator->exists) {
                            if (updateIfChanged($generatedOperator, $operatorAttrs)) {
                                $this->info("Updated operator: {$generatedOperator->name}");
                            }
                        } else {
                            $generatedOperator->fill($operatorAttrs)->save();
                            $this->info("Created operator: {$generatedOperator->name}");
                        }
                        $airaloOperatorIds[] = $generatedOperator->id;

                        // --- Packages ---
                        foreach ($operator['packages'] as $pkg) {
                            if (empty($pkg['id'])) {
                                continue;
                            }

                            $esimPackageGenerated = EsimPackage::firstOrNew(['airalo_package_id' => $pkg['id']]);
                            $pkgAttrs = [
                                'airalo_package_id' => $pkg['id'],
                                'name' => $pkg['title'],
                                'operator_id' => $generatedOperator->id,
                                'type' => $pkg['type'],
                                'price' => $pkg['price'],
                                'amount' => $pkg['amount'],
                                'day' => $pkg['day'],
                                'is_unlimited' => $pkg['is_unlimited'],
                                'short_info' => $pkg['short_info'],
                                'qr_installation' => $pkg['qr_installation'],
                                'manual_installation' => $pkg['manual_installation'],
                                'is_fair_usage_policy' => $pkg['is_fair_usage_policy'],
                                'fair_usage_policy' => $pkg['fair_usage_policy'],
                                'data' => $pkg['data'],
                                'net_price' => $pkg['net_price'],
                                'prices' => $pkg['prices'],
                                'is_active' => 1
                            ];

                            $shouldDispatch = false;

                            if ($esimPackageGenerated->exists) {
                                if (updateIfChanged($esimPackageGenerated, $pkgAttrs)) {
                                    $this->info("Updated package: {$esimPackageGenerated->name}");
                                    $shouldDispatch = true;
                                }
                            } else {
                                $esimPackageGenerated->fill($pkgAttrs)->save();
                                $this->info("Created package: {$esimPackageGenerated->name}");
                                $shouldDispatch = true;
                            }

                            if ($shouldDispatch) {
                                $adminInclude = (float) systemflag('PackageCommission');
                                $airaloPrice  = (float) ($esimPackageGenerated->prices['net_price']['INR'] ?? 0);
                                $includePrice = ($airaloPrice * $adminInclude) / 100;
                                $netPrice = $airaloPrice + $includePrice;
                                $netPrice = ceil($netPrice / 5) * 5;

                                CreateInAppProductJob::dispatch(
                                    $esimPackageGenerated->airalo_package_id,
                                    $esimPackageGenerated->name,
                                    $esimPackageGenerated->short_info ?? 'Plans Description',
                                    $netPrice,
                                    'INR'
                                );
                            }

                            $airaloPackageIds[] = $esimPackageGenerated->id;
                            $count++;
                        }
                    }
                }
                $page++;
            } while (!empty($response['data']));

            // Deactivate missing operators/packages
            Operator::where('type', 'local')
                ->whereNotIn('id', $airaloOperatorIds)
                ->update(['airalo_active' => 0]);

            EsimPackage::whereIn('operator_id', $airaloOperatorIds)
                ->whereNotIn('id', $airaloPackageIds)
                ->update(['airalo_active' => 0]);

            $this->info("Airalo package sync complete. Synced $count packages.");
        } catch (\Exception $e) {
            $this->error('Error syncing Airalo packages: ' . $e->getMessage());
            return 1;
        }

        return 0;
    }
}
