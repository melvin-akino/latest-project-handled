import Vue from 'vue'
import Vuelidate from 'vuelidate'
Vue.use(Vuelidate)

import {shallowMount} from '@vue/test-utils'
import Register from '../components/views/auth/Register.vue'

const wrapper = shallowMount(Register, {
    stubs:['router-link']
})

const testRequiredFieldsOnButtonClick = (requiredFields, step) => {
    requiredFields.forEach(field => {
        const validation = wrapper.vm.$v.registerForm
        wrapper.find(`#${field}`).setValue('' || null)
        wrapper.find('button').trigger('click')
        expect(validation[step][field].required).toBe(false)
    })
}

const testRequiredFieldsOnTyping = (requiredFields, step) => {
    requiredFields.forEach(field => {
        const validation = wrapper.vm.$v.registerForm
        wrapper.find(`#${field}`).trigger('keydown.up')
        wrapper.find(`#${field}`).setValue('' || null)
        expect(validation[step][field].required).toBe(false)
    })
}

describe('Register invalid parameters test', () => {
    it('Required validation rule should be false on button click or typing when field is empty or null in step 1', () => {
        let requiredFields = ['name', 'firstname', 'lastname', 'email', 'password']
        testRequiredFieldsOnButtonClick(requiredFields, 'step1')
        testRequiredFieldsOnTyping(requiredFields, 'step1')
    })

    it('Required validation rule should be false on button click or typing when field is empty or null in step 2', async () => {
        wrapper.vm.step = 2
        await Vue.nextTick()
        let requiredFields = ['address', 'country', 'state', 'city', 'postcode', 'phone', 'phone_country_code']
        testRequiredFieldsOnButtonClick(requiredFields, 'step2')
        testRequiredFieldsOnTyping(requiredFields, 'step2')
    })

    it('Required validation rule should be false on button click or typing when field is empty or null in step 3', async () => {
        wrapper.vm.step = 3
        await Vue.nextTick()
        let requiredFields = ['odds_type', 'currency_id']
        testRequiredFieldsOnButtonClick(requiredFields, 'step3')
        testRequiredFieldsOnTyping(requiredFields, 'step3')
    })

    it('Email validation rule should be false on button click when input is invalid', () => {
        const validation = wrapper.vm.$v.registerForm
        wrapper.find('#email').setValue('randomstring')
        wrapper.find('button').trigger('click')
        expect(validation.step1.email.email).toBe(false)
    })

    it('Email validation rule should be false on typing click when input is invalid', () => {
        const validation = wrapper.vm.$v.registerForm
        wrapper.find('#email').trigger('keydown.up')
        wrapper.find('#email').setValue('randomstring')
        expect(validation.step1.email.email).toBe(false)
    })

    it('Minlength validation rule should be false on button click when username and password fields have less than 6 characters', () => {
        const validation = wrapper.vm.$v.registerForm
        wrapper.find('#name').setValue('test')
        wrapper.find('#password').setValue('1234')
        wrapper.find('button').trigger('click')
        expect(validation.step1.name.minLength).toBe(false)
        expect(validation.step1.password.minLength).toBe(false)
    })

    it('Minlength validation rule should be false on typing when username and password fields have less than 6 characters', () => {
        const validation = wrapper.vm.$v.registerForm
        wrapper.find('#name').trigger('keydown.up')
        wrapper.find('#name').setValue('test')
        wrapper.find('#password').trigger('keydown.up')
        wrapper.find('#password').setValue('1234')
        expect(validation.step1.name.minLength).toBe(false)
        expect(validation.step1.password.minLength).toBe(false)
    })

    it('Maxlength validation rule should be false on button click when username and password fields have more than 32 characters', () => {
        const validation = wrapper.vm.$v.registerForm
        wrapper.find('#name').setValue('gqkW9pR2w3s2f6n7T4J4hTt0mgwAIh0Gs')
        wrapper.find('#password').setValue('gqkW9pR2w3s2f6n7T4J4hTt0mgwAIh0Gs')
        wrapper.find('button').trigger('click')
        expect(validation.step1.name.maxLength).toBe(false)
        expect(validation.step1.password.maxLength).toBe(false)
    })

    it('Maxlength validation rule should be false on typing when username and password fields have more than 32 characters', () => {
        const validation = wrapper.vm.$v.registerForm
        wrapper.find('#name').trigger('keydown.up')
        wrapper.find('#name').setValue('gqkW9pR2w3s2f6n7T4J4hTt0mgwAIh0Gs')
        wrapper.find('#password').trigger('keydown.up')
        wrapper.find('#password').setValue('gqkW9pR2w3s2f6n7T4J4hTt0mgwAIh0Gs')
        expect(validation.step1.name.maxLength).toBe(false)
        expect(validation.step1.password.maxLength).toBe(false)
    })

    it('Alphanum validation rule should be false on button click when username field input is invalid', () => {
        const validation = wrapper.vm.$v.registerForm
        wrapper.find('#name').setValue('$!@*hey')
        wrapper.find('button').trigger('click')
        expect(validation.step1.name.alphaNum).toBe(false)
    })

    it('Alphanum validation rule should be false on typing when username field input is invalid', () => {
        const validation = wrapper.vm.$v.registerForm
        wrapper.find('#name').trigger('keydown.up')
        wrapper.find('#name').setValue('$!@*hey')
        expect(validation.step1.name.alphaNum).toBe(false)
    })

    it('Password same as validation rule should be false on button click when password and password confirmation does not match', () => {
        const validation = wrapper.vm.$v.registerForm
        wrapper.find('#password').setValue('1234asdf')
        wrapper.find('#password_confirmation').setValue('1234qwer')
        wrapper.find('button').trigger('click')
        expect(validation.step1.password_confirmation.sameAs).toBe(false)
    })

    it('Password same as validation rule should be false on button click when password and password confirmation does not match', () => {
        const validation = wrapper.vm.$v.registerForm
        wrapper.find('#password').trigger('keydown.up')
        wrapper.find('#password').setValue('1234asdf')
        wrapper.find('#password_confirmation').trigger('keydown.up')
        wrapper.find('#password_confirmation').setValue('1234qwer')
        expect(validation.step1.password_confirmation.sameAs).toBe(false)
    })

    it('Numeric validation rule should be false on button click when phone and country code have non numeric value', async () => {
        const validation = wrapper.vm.$v.registerForm
        wrapper.vm.step = 2
        await Vue.nextTick()
        wrapper.find('#phone').setValue('1234asdf')
        wrapper.find('#phone_country_code').setValue('1234asdf')
        wrapper.find('button').trigger('click')
        expect(validation.step2.phone.numeric).toBe(false)
        expect(validation.step2.phone_country_code.numeric).toBe(false)
    })

    it('Numeric validation rule should be false on typing when phone and country code have non numeric value', async () => {
        const validation = wrapper.vm.$v.registerForm
        wrapper.vm.step = 2
        await Vue.nextTick()
        wrapper.find('#phone').trigger('keydown.up')
        wrapper.find('#phone').setValue('1234asdf')
        wrapper.find('#phone_country_code').trigger('keydown.up')
        wrapper.find('#phone_country_code').setValue('1234asdf')
        expect(validation.step2.phone.numeric).toBe(false)
        expect(validation.step2.phone_country_code.numeric).toBe(false)
    })
})
