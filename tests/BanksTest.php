<?php

declare(strict_types=1);

namespace PhDevUtils\Banks\Tests;

use PHPUnit\Framework\TestCase;
use PhDevUtils\Banks\Banks;

final class BanksTest extends TestCase
{
    public function testListBanksReturnsFullRegistry(): void
    {
        $this->assertGreaterThan(140, count(Banks::listBanks()));
    }

    public function testFilterByType(): void
    {
        $ukb = Banks::listBanks(['type' => 'universal_commercial']);
        $this->assertGreaterThan(30, count($ukb));
        foreach ($ukb as $b) {
            $this->assertSame('universal_commercial', $b['type']);
        }

        $ewallets = Banks::listBanks(['type' => 'ewallet']);
        $names = array_map(static fn ($b) => $b['shortName'], $ewallets);
        $this->assertContains('GCash', $names);
        $this->assertContains('Maya', $names);
    }

    public function testFilterByParticipationAndSwift(): void
    {
        foreach (Banks::listBanks(['instapay' => true]) as $b) {
            $this->assertTrue($b['instapay']);
        }
        foreach (Banks::listBanks(['hasSwift' => true]) as $b) {
            $this->assertNotNull($b['swift']);
        }
        foreach (Banks::listBanks(['foreign' => true]) as $b) {
            $this->assertTrue($b['foreign']);
        }
    }

    public function testFindBank(): void
    {
        $this->assertSame('BDO Unibank, Inc.', Banks::findBank('BDO')['name']);
        $this->assertSame('BPI', Banks::findBank('Bank of the Philippine Islands')['shortName']);
        $this->assertSame('MBTCPHMM', Banks::findBank('metrobank')['swift']);
        $this->assertSame('BDO', Banks::findBank('BNORPHMM')['shortName']);
        $this->assertSame('UnionBank', Banks::findBank('UBPHPHMMXXX')['shortName']);
        $this->assertNull(Banks::findBank('Bank of Nowhere'));
        $this->assertNull(Banks::findBank(''));
    }

    public function testFindBySwift(): void
    {
        $this->assertSame('RCBC', Banks::findBySwift('rcbcphmm')['shortName']);
        $this->assertSame('RCBC', Banks::findBySwift('RCBCPHMMXXX')['shortName']);
        $this->assertNull(Banks::findBySwift('ZZZZPHMM'));
    }

    public function testValidateBic(): void
    {
        $this->assertTrue(Banks::validateBIC('BNORPHMM'));
        $this->assertTrue(Banks::validateBIC('bopiphmmxxx'));
        $this->assertTrue(Banks::validateBIC('BOFAPH2X'));
        $this->assertFalse(Banks::validateBIC('CITIPHMXV')); // 9 chars
        $this->assertFalse(Banks::validateBIC('BNOR'));
        $this->assertFalse(Banks::validateBIC('1234PHMM'));
        $this->assertFalse(Banks::validateBIC(''));
    }

    public function testParseBic(): void
    {
        $this->assertSame(
            ['institution' => 'BOPI', 'country' => 'PH', 'location' => 'MM', 'branch' => null],
            Banks::parseBIC('BOPIPHMM'),
        );
        $this->assertSame('XXX', Banks::parseBIC('RCBCPHMMXXX')['branch']);
        $this->assertNull(Banks::parseBIC('nope'));
    }

    public function testDataIntegrity(): void
    {
        foreach (Banks::listBanks() as $b) {
            if ($b['swift'] !== null) {
                $this->assertTrue(Banks::validateBIC($b['swift']), (string) $b['name']);
                $this->assertContains($b['swiftConfidence'], ['verified', 'single-source']);
            } else {
                $this->assertNull($b['swiftConfidence']);
            }
            if ($b['instapayReceiverOnly']) {
                $this->assertTrue($b['instapay']);
            }
        }
        $this->assertMatchesRegularExpression('/^\d{4}-\d{2}-\d{2}$/', Banks::participationAsOf());
    }
}
