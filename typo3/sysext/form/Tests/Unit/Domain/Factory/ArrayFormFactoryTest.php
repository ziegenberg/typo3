<?php

declare(strict_types=1);

/*
 * This file is part of the TYPO3 CMS project.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 * The TYPO3 project - inspiring people to share!
 */

namespace TYPO3\CMS\Form\Tests\Unit\Domain\Factory;

use TYPO3\CMS\Form\Domain\Exception\IdentifierNotValidException;
use TYPO3\CMS\Form\Domain\Factory\ArrayFormFactory;
use TYPO3\CMS\Form\Domain\Model\FormElements\Section;
use TYPO3\CMS\Form\Domain\Model\FormElements\UnknownFormElement;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

class ArrayFormFactoryTest extends UnitTestCase
{
    /**
     * @test
     */
    public function addNestedRenderableThrowsExceptionIfIdentifierIsMissing(): void
    {
        $this->expectException(IdentifierNotValidException::class);
        $this->expectExceptionCode(1329289436);

        $section = new Section('test', 'page');
        $arrayFormFactory = $this->getAccessibleMock(ArrayFormFactory::class, ['dummy']);

        $arrayFormFactory->_call('addNestedRenderable', [], $section);
    }

    /**
     * @test
     */
    public function addNestedRenderableSkipChildElementRenderingIfCompositeElementIsUnknown(): void
    {
        $unknownElement = new UnknownFormElement('test-2', 'test');
        $section = $this->createMock(Section::class);
        $section->method('createElement')->with(self::anything())->willReturn($unknownElement);
        $configuration = [
            'identifier' => 'test-3',
            'type' => 'Foo',
        ];
        $arrayFormFactory = $this->getAccessibleMock(ArrayFormFactory::class, ['dummy']);
        $result = $arrayFormFactory->_call('addNestedRenderable', $configuration, $section);
        self::assertSame($unknownElement, $result);
    }
}
