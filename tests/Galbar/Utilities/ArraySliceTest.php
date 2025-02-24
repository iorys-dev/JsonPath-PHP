<?php
/**
 * Copyright 2018 Alessio Linares
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *     http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */

namespace Tests\Utilities;

use Exception;
use PHPUnit\Framework\TestCase;
use Utilities\ArraySlice;

/**
 * Class ArraySliceTest
 * @author Alessio Linares
 */
class ArraySliceTestrraySliceTest extends TestCase
{
    /**
     * testSlice
     *
     * @param  array  $expected  expected
     * @param  array  $array  array
     * @param  mixed  $start  start
     * @param  mixed  $stop  stop
     * @param  mixed  $step  step
     * @param  boolean  $byReference  byReference
     *
     * @return void
     *
     * @dataProvider testSliceProvider
     */
    public function testSlice($expected, $array, $start = null, $stop = null, $step = null)
    {
        $this->assertEquals($expected, ArraySlice::slice($array, $start, $stop, $step));
    }

    public function testSliceByReference()
    {
        $original = [0, 1, 2, 3, 4, 5, 6, 7, 8, 9];
        $result = ArraySlice::slice($original, 1, 8, 2);
        $result[2] = -1;
        $this->assertEquals(5, $original[5]);
        $result = ArraySlice::slice($original, 1, 8, 2, true);
        $result[2] = -1;
        $this->assertEquals(-1, $original[5]);
    }

    public function testSliceException()
    {
        $exception = null;
        $array = [0, 1, 2, 3];
        try {
            ArraySlice::slice($array, 1, 4, 0);
        } catch (Exception $e) {
            $exception = $e;
        }
        $this->assertEquals("Step cannot be 0", $exception->getMessage());
    }

    public function testSliceProvider()
    {
        return [
            [
                [1, 2, 3, 4, 5],
                [0, 1, 2, 3, 4, 5, 6, 7, 8, 9],
                1,
                6
            ],
            [
                [1, 2, 3, 4, 5, 6, 7, 8],
                [0, 1, 2, 3, 4, 5, 6, 7, 8, 9],
                1,
                -1
            ],
            [
                [],
                [0, 1, 2, 3, 4, 5, 6, 7, 8, 9],
                1,
                -12
            ],
            [
                [1, 0],
                [0, 1, 2, 3, 4, 5, 6, 7, 8, 9],
                1,
                -12,
                -1
            ],
            [
                [],
                [0, 1, 2, 3, 4, 5, 6, 7, 8, 9],
                12,
                5
            ],
            [
                [9, 8, 7, 6],
                [0, 1, 2, 3, 4, 5, 6, 7, 8, 9],
                12,
                5,
                -1
            ],
            [
                [1, 2, 3, 4, 5, 6, 7, 8, 9],
                [0, 1, 2, 3, 4, 5, 6, 7, 8, 9],
                1,
            ],
            [
                [0, 1, 2, 3, 4],
                [0, 1, 2, 3, 4, 5, 6, 7, 8, 9],
                null,
                5
            ]
        ];
    }
}
